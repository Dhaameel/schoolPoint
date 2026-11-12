<?php

namespace App\Queries\Attendance;

use App\Models\Attendance;
use App\Models\Student;
use App\Support\SchoolDays;
use Carbon\Carbon;

class StudentAttendanceSummary
{
    public function forStudent(Student $student, Carbon $start, Carbon $end, array $schoolDays): array
    {
        $rows = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->filter(fn ($row) => SchoolDays::isSchoolDay($row->date, $schoolDays));

        $present = $rows->where('status', 'present')->count();
        $absent = $rows->where('status', 'absent')->count();
        $late = $rows->where('status', 'late')->count();
        $schoolDayCount = SchoolDays::countBetween($start, $end, $schoolDays);

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'school_days' => $schoolDayCount,
            'percentage' => $schoolDayCount > 0 ? round(($present / $schoolDayCount) * 100, 1) : 0.0,
        ];
    }
}
