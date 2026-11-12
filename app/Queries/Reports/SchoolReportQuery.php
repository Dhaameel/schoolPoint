<?php

namespace App\Queries\Reports;

use App\Models\Attendance;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\ReportPeriod;
use App\Support\SchoolDays;

class SchoolReportQuery
{
    public function forPeriod(ReportPeriod $period, array $schoolDays): array
    {
        $schoolDates = SchoolDays::datesBetween($period->start, $period->end, $schoolDays);
        $schoolDayCount = count($schoolDates);

        $studentStatusCounts = Student::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $classes = SchoolClass::with('classTeacher.user')
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'class_teacher_id']);

        $attendanceByClass = Attendance::whereBetween('date', [$period->startDate(), $period->endDate()])
            ->get(['class_id', 'date', 'status'])
            ->filter(fn ($row) => in_array($row->date->toDateString(), $schoolDates, true))
            ->groupBy('class_id')
            ->map(fn ($rows) => $rows->groupBy('status')->map->count());

        $classRows = $classes->map(function (SchoolClass $class) use ($attendanceByClass, $schoolDayCount) {
            $counts = $attendanceByClass->get($class->id, collect());
            $present = (int) ($counts['present'] ?? 0);
            $possible = $class->students_count * $schoolDayCount;

            return [
                'class' => $class,
                'students' => $class->students_count,
                'present' => $present,
                'absent' => (int) ($counts['absent'] ?? 0),
                'late' => (int) ($counts['late'] ?? 0),
                'percentage' => $possible > 0 ? round(($present / $possible) * 100, 1) : 0.0,
            ];
        });

        $present = $classRows->sum('present');
        $absent = $classRows->sum('absent');
        $late = $classRows->sum('late');
        $activeStudents = (int) ($studentStatusCounts['active'] ?? 0);
        $possible = $activeStudents * $schoolDayCount;

        return [
            'students_total' => $studentStatusCounts->sum(),
            'students_active' => $activeStudents,
            'students_graduated' => (int) ($studentStatusCounts['graduated'] ?? 0),
            'students_left' => (int) ($studentStatusCounts['left'] ?? 0),
            'classes_total' => $classes->count(),
            'classes_active' => $classes->where('status', 'active')->count(),
            'teachers_total' => Teacher::count(),
            'teachers_active' => Teacher::where('status', 'active')->count(),
            'subjects_total' => Subject::count(),
            'school_days' => $schoolDayCount,
            'attendance' => [
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'possible' => $possible,
                'percentage' => $possible > 0 ? round(($present / $possible) * 100, 1) : 0.0,
            ],
            'class_rows' => $classRows,
            'fees_billed' => $this->feesBilled($period),
            'fees_collected' => $this->feesCollected($period),
            'outstanding' => $this->outstanding(),
            'fee_status_counts' => Fee::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ];
    }

    private function feesBilled(ReportPeriod $period): array
    {
        $totals = [];

        foreach (Fee::where('status', '!=', 'void')
            ->whereBetween('billed_at', [$period->start, $period->end])
            ->get(['amount', 'currency']) as $fee) {
            $this->add($totals, $fee->currency, (string) $fee->amount);
        }

        return $totals;
    }

    private function feesCollected(ReportPeriod $period): array
    {
        $totals = [];

        $payments = FeePayment::with('fee:id,currency')
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->get(['fee_id', 'type', 'amount']);

        foreach ($payments as $payment) {
            $currency = $payment->fee?->currency;

            if (! $currency) {
                continue;
            }

            if ($payment->type === 'payment') {
                $this->add($totals, $currency, (string) $payment->amount);
            } else {
                $totals[$currency] = bcsub($totals[$currency] ?? '0.00', (string) $payment->amount, 2);
            }
        }

        return $totals;
    }

    private function outstanding(): array
    {
        $totals = [];

        foreach (Fee::where('status', '!=', 'void')->get(['amount', 'amount_paid', 'currency']) as $fee) {
            $balance = bcsub((string) $fee->amount, (string) $fee->amount_paid, 2);

            if (bccomp($balance, '0.00', 2) > 0) {
                $this->add($totals, $fee->currency, $balance);
            }
        }

        return $totals;
    }

    private function add(array &$totals, string $currency, string $amount): void
    {
        $totals[$currency] = bcadd($totals[$currency] ?? '0.00', $amount, 2);
    }
}
