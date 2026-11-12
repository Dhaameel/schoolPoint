<?php

namespace App\Actions\Fees;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\User;
use App\Support\Audit;
use App\Support\DatabaseError;
use App\Support\FeeBalance;
use App\Support\ReceiptNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReverseFeePaymentAction
{
    public function handle(Fee $fee, FeePayment $payment, string $reason, User $user): FeePayment
    {
        return DB::transaction(function () use ($fee, $payment, $reason, $user) {
            $fee = Fee::whereKey($fee->id)->lockForUpdate()->firstOrFail();
            $payment = FeePayment::whereKey($payment->id)
                ->where('fee_id', $fee->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fee->status === 'void') {
                throw ValidationException::withMessages([
                    'reason' => __('messages.reversal_fee_void'),
                ]);
            }

            if ($payment->type !== 'payment') {
                throw ValidationException::withMessages([
                    'reason' => __('messages.reversal_of_reversal'),
                ]);
            }

            if (FeePayment::where('reversal_of_id', $payment->id)->exists()) {
                throw ValidationException::withMessages([
                    'reason' => __('messages.reversal_already'),
                ]);
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => __('messages.reversal_reason_required'),
                ]);
            }

            $reference = 'REV-'.$payment->reference;

            if (FeePayment::where('reference', $reference)->exists()) {
                $reference = 'REV-'.$payment->id.'-'.strtolower(Str::random(8));
            }

            try {
                $reversal = FeePayment::create([
                    'fee_id' => $fee->id,
                    'reference' => $reference,
                    'receipt_number' => ReceiptNumber::next(now()),
                    'amount' => $payment->amount,
                    'type' => 'reversal',
                    'reversal_of_id' => $payment->id,
                    'reason' => $reason,
                    'paid_at' => now(),
                    'method' => $payment->method,
                    'note' => __('messages.reversal_of', ['receipt' => $payment->receipt_number]),
                    'created_by' => $user->id,
                ]);
            } catch (QueryException $exception) {
                if (DatabaseError::isUniqueViolation($exception)) {
                    throw ValidationException::withMessages([
                        'reason' => __('messages.reversal_already'),
                    ]);
                }

                throw $exception;
            }

            $netPaid = FeeBalance::netPaid($fee);

            $fee->update([
                'amount_paid' => $netPaid,
                'status' => FeeBalance::status($fee, $netPaid),
            ]);

            Audit::record('fees.payment_reversed', 'Fee payment reversed.', [
                'fee_id' => $fee->id,
                'payment_id' => $payment->id,
                'reversal_id' => $reversal->id,
                'receipt_number' => $reversal->receipt_number,
                'amount' => $reversal->amount,
                'reason' => $reason,
            ]);

            return $reversal;
        }, 3);
    }
}
