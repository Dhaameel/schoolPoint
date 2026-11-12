<?php

namespace App\Queries\Reports;

use App\Models\Attendance;
use App\Models\Teacher;
use App\Support\ReportPeriod;

class TeacherReportQuery
{
    public function forTeacher(Teacher $teacher, ReportPeriod $period): array
    {
        $classTeacherClasses = $teacher->classTeacherClasses()
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectAssignments = $teacher->classSubjects()
            ->with(['schoolClass:id,name', 'subject:id,name'])
            ->get();

        $attendance = Attendance::where('marked_by', $teacher->user_id)
            ->whereBetween('date', [$period->startDate(), $period->endDate()])
            ->get(['date', 'status', 'class_id']);

        $counts = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
        ];

        return [
            'class_teacher_classes' => $classTeacherClasses,
            'subject_assignments' => $subjectAssignments,
            'attendance' => [
                'total' => $attendance->count(),
                'present' => $counts['present'],
                'absent' => $counts['absent'],
                'late' => $counts['late'],
                'days' => $attendance->map(fn ($row) => $row->date->toDateString())->unique()->count(),
                'classes' => $attendance->pluck('class_id')->unique()->count(),
            ],
        ];
    }
}
