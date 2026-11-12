<?php

namespace App\Actions\Fees;

use App\Models\Fee;
use App\Support\Audit;
use App\Support\FeeBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidFeeAction
{
    public function handle(Fee $fee, string $reason): Fee
    {
        return DB::transaction(function () use ($fee, $reason) {
            $fee = Fee::whereKey($fee->id)->lockForUpdate()->firstOrFail();

            if ($fee->status === 'void') {
                throw ValidationException::withMessages([
                    'reason' => __('messages.fee_already_void'),
                ]);
            }

            $netPaid = FeeBalance::netPaid($fee);

            if (bccomp($netPaid, '0.00', 2) !== 0) {
                throw ValidationException::withMessages([
                    'reason' => __('messages.void_blocked_payments'),
                ]);
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => __('messages.void_reason_required'),
                ]);
            }

            $fee->update([
                'amount_paid' => '0.00',
                'status' => 'void',
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            Audit::record('fees.voided', 'Fee voided.', [
                'fee_id' => $fee->id,
                'reason' => $reason,
            ]);

            return $fee;
        }, 3);
    }
}
