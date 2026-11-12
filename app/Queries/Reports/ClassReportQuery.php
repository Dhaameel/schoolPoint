<?php

namespace App\Queries\Reports;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Queries\Attendance\ClassAttendanceSummary;
use App\Support\FeeBalance;
use App\Support\ReportPeriod;

class ClassReportQuery
{
    public function forClass(SchoolClass $class, ReportPeriod $period, array $schoolDays): array
    {
        $payments = FeePayment::with('fee:id,currency')
            ->whereHas('fee', fn ($query) => $query->where('class_id', $class->id))
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->get(['fee_id', 'type', 'amount']);

        $collected = [];

        foreach ($payments as $payment) {
            $currency = $payment->fee?->currency;

            if (! $currency) {
                continue;
            }

            $collected[$currency] = $payment->type === 'payment'
                ? bcadd($collected[$currency] ?? '0.00', (string) $payment->amount, 2)
                : bcsub($collected[$currency] ?? '0.00', (string) $payment->amount, 2);
        }

        $outstanding = [];
        $owing = [];

        $fees = Fee::where('class_id', $class->id)
            ->where('status', '!=', 'void')
            ->with(['payments', 'student'])
            ->get();

        foreach ($fees as $fee) {
            $balance = FeeBalance::balance($fee);

            if (bccomp($balance, '0.00', 2) <= 0 || ! $fee->student) {
                continue;
            }

            $outstanding[$fee->currency] = bcadd($outstanding[$fee->currency] ?? '0.00', $balance, 2);

            $studentId = $fee->student_id;

            if (! isset($owing[$studentId])) {
                $owing[$studentId] = [
                    'student' => $fee->student,
                    'balances' => [],
                ];
            }

            $owing[$studentId]['balances'][$fee->currency] = bcadd(
                $owing[$studentId]['balances'][$fee->currency] ?? '0.00',
                $balance,
                2
            );
        }

        $owing = collect($owing)
            ->sortBy(fn ($row) => $row['student']->name)
            ->values();

        return [
            'attendance' => (new ClassAttendanceSummary)->forClass($class->id, $period->start, $period->end, $schoolDays),
            'collected' => $collected,
            'outstanding' => $outstanding,
            'owing' => $owing,
        ];
    }
}
