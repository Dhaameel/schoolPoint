<?php

namespace App\Actions\Fees;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\User;
use App\Support\Audit;
use App\Support\DatabaseError;
use App\Support\FeeBalance;
use App\Support\Money;
use App\Support\ReceiptNumber;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordFeePaymentAction
{
    public function handle(Fee $fee, array $data, User $user): FeePayment
    {
        return DB::transaction(function () use ($fee, $data, $user) {
            $fee = Fee::whereKey($fee->id)->lockForUpdate()->firstOrFail();

            if ($fee->status === 'void') {
                throw ValidationException::withMessages([
                    'amount' => __('messages.payment_fee_void'),
                ]);
            }

            $reference = trim((string) $data['reference']);

            if (FeePayment::where('reference', $reference)->exists()) {
                throw ValidationException::withMessages([
                    'reference' => __('messages.payment_reference_used'),
                ]);
            }

            $amount = Money::normalize($data['amount']);

            if (bccomp($amount, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('messages.payment_amount_positive'),
                ]);
            }

            $balance = bcsub((string) $fee->amount, FeeBalance::netPaid($fee), 2);

            if (bccomp($balance, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('messages.payment_fee_paid'),
                ]);
            }

            if (bccomp($amount, $balance, 2) === 1) {
                throw ValidationException::withMessages([
                    'amount' => __('messages.payment_over_balance', [
                        'balance' => Money::format($balance, $fee->currency),
                    ]),
                ]);
            }

            try {
                $payment = FeePayment::create([
                    'fee_id' => $fee->id,
                    'reference' => $reference,
                    'receipt_number' => ReceiptNumber::next(now()),
                    'amount' => $amount,
                    'type' => 'payment',
                    'paid_at' => Carbon::parse($data['paid_at'])->setTimeFrom(now()),
                    'method' => $data['method'] ?? null,
                    'note' => $data['note'] ?? null,
                    'created_by' => $user->id,
                ]);
            } catch (QueryException $exception) {
                if (DatabaseError::isUniqueViolation($exception)) {
                    throw ValidationException::withMessages([
                        'reference' => __('messages.payment_reference_used'),
                    ]);
                }

                throw $exception;
            }

            $this->refreshFee($fee);

            Audit::record('fees.payment_recorded', 'Fee payment recorded.', [
                'fee_id' => $fee->id,
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
            ]);

            return $payment;
        }, 3);
    }

    private function refreshFee(Fee $fee): void
    {
        $netPaid = FeeBalance::netPaid($fee);

        $fee->update([
            'amount_paid' => $netPaid,
            'status' => FeeBalance::status($fee, $netPaid),
        ]);
    }
}
