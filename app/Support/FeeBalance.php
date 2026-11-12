<?php

namespace App\Support;

use App\Models\Fee;

class FeeBalance
{
    public static function netPaid(Fee $fee): string
    {
        $payments = $fee->relationLoaded('payments')
            ? $fee->payments
            : $fee->payments()->get(['type', 'amount']);

        $total = '0.00';

        foreach ($payments as $payment) {
            $total = $payment->type === 'payment'
                ? bcadd($total, (string) $payment->amount, 2)
                : bcsub($total, (string) $payment->amount, 2);
        }

        return $total;
    }

    public static function balance(Fee $fee): string
    {
        if ($fee->status === 'void') {
            return '0.00';
        }

        return bcsub((string) $fee->amount, (string) $fee->amount_paid, 2);
    }

    public static function status(Fee $fee, string $netPaid): string
    {
        if ($fee->status === 'void') {
            return 'void';
        }

        if (bccomp($netPaid, '0.00', 2) <= 0) {
            return 'unpaid';
        }

        if (bccomp($netPaid, (string) $fee->amount, 2) < 0) {
            return 'partial';
        }

        return 'paid';
    }
}
