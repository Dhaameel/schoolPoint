<?php

namespace App\Queries\Attendance;

use App\Models\Student;
use App\Support\SchoolDays;
use Carbon\Carbon;

class ClassAttendanceSummary
{
    public function forClass(int $classId, Carbon $start, Carbon $end, array $schoolDays): array
    {
        $students = Student::where('class_id', $classId)
            ->where(function ($query) use ($start, $end) {
                $query->where('status', 'active')
                    ->orWhereHas('attendances', fn ($query) => $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]));
            })
            ->orderBy('first_name')
            ->get();

        $summary = new StudentAttendanceSummary();
        $rows = $students->map(fn (Student $student) => [
            'student' => $student,
            'summary' => $summary->forStudent($student, $start, $end, $schoolDays),
        ]);

        $present = $rows->sum(fn ($row) => $row['summary']['present']);
        $absent = $rows->sum(fn ($row) => $row['summary']['absent']);
        $late = $rows->sum(fn ($row) => $row['summary']['late']);
        $schoolDayCount = SchoolDays::countBetween($start, $end, $schoolDays);
        $possible = $schoolDayCount * $students->count();

        return [
            'students' => $rows,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'school_days' => $schoolDayCount,
            'percentage' => $possible > 0 ? round(($present / $possible) * 100, 1) : 0.0,
        ];
    }
}
