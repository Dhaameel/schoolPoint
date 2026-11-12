<?php

namespace App\Queries\Reports;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Student;
use App\Queries\Attendance\StudentAttendanceSummary;
use App\Support\FeeBalance;
use App\Support\ReportPeriod;

class StudentReportQuery
{
    public function forStudent(Student $student, ReportPeriod $period, array $schoolDays): array
    {
        $billedFees = Fee::where('student_id', $student->id)
            ->where('status', '!=', 'void')
            ->whereBetween('billed_at', [$period->start, $period->end])
            ->get(['amount', 'currency']);

        $feesBilled = [];

        foreach ($billedFees as $fee) {
            $feesBilled[$fee->currency] = bcadd(
                $feesBilled[$fee->currency] ?? '0.00',
                (string) $fee->amount,
                2
            );
        }

        $payments = FeePayment::with('fee:id,currency')
            ->whereHas('fee', fn ($query) => $query->where('student_id', $student->id))
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->get(['fee_id', 'type', 'amount']);

        $feesPaid = [];

        foreach ($payments as $payment) {
            $currency = $payment->fee?->currency;

            if (! $currency) {
                continue;
            }

            $feesPaid[$currency] = $payment->type === 'payment'
                ? bcadd($feesPaid[$currency] ?? '0.00', (string) $payment->amount, 2)
                : bcsub($feesPaid[$currency] ?? '0.00', (string) $payment->amount, 2);
        }

        $balance = [];

        foreach (Fee::where('student_id', $student->id)->where('status', '!=', 'void')->with('payments')->get() as $fee) {
            $outstanding = FeeBalance::balance($fee);

            if (bccomp($outstanding, '0.00', 2) <= 0) {
                continue;
            }

            $balance[$fee->currency] = bcadd($balance[$fee->currency] ?? '0.00', $outstanding, 2);
        }

        return [
            'fees_billed' => $feesBilled,
            'fees_paid' => $feesPaid,
            'balance' => $balance,
            'attendance' => (new StudentAttendanceSummary)->forStudent($student, $period->start, $period->end, $schoolDays),
        ];
    }
}
