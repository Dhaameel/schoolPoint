<?php

namespace App\Queries\Reports;

use App\Models\Fee;

class FeeAnalyticsQuery
{
    public function snapshot(): array
    {
        $fees = Fee::get(['amount', 'amount_paid', 'currency', 'status']);
        $billed = [];
        $collected = [];
        $outstanding = [];
        $rates = [];

        foreach ($fees as $fee) {
            if ($fee->status === 'void') {
                continue;
            }

            $this->add($billed, $fee->currency, (string) $fee->amount);
            $this->add($collected, $fee->currency, (string) $fee->amount_paid);

            $balance = bcsub((string) $fee->amount, (string) $fee->amount_paid, 2);

            if (bccomp($balance, '0.00', 2) > 0) {
                $this->add($outstanding, $fee->currency, $balance);
            }
        }

        foreach ($billed as $currency => $amount) {
            $rates[$currency] = bccomp($amount, '0.00', 2) > 0
                ? round((((float) $collected[$currency]) / ((float) $amount)) * 100, 1)
                : 0.0;
        }

        return [
            'billed' => $billed,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'rates' => $rates,
            'status_counts' => Fee::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'total_fees' => $fees->count(),
        ];
    }

    private function add(array &$totals, string $currency, string $amount): void
    {
        $totals[$currency] = bcadd($totals[$currency] ?? '0.00', $amount, 2);
    }
}
