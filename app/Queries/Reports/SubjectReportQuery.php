<?php

namespace App\Queries\Reports;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Queries\Attendance\ClassAttendanceSummary;
use App\Support\ReportPeriod;
use App\Support\SchoolDays;

class SubjectReportQuery
{
    public function forSubject(Subject $subject, ReportPeriod $period, array $schoolDays): array
    {
        $assignments = ClassSubject::with(['schoolClass.classTeacher.user', 'teacher.user'])
            ->where('subject_id', $subject->id)
            ->get();

        $assignmentsByClass = $assignments->keyBy('class_id');

        $classes = SchoolClass::with('classTeacher.user')
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])
            ->whereIn('id', $assignments->pluck('class_id'))
            ->orderBy('name')
            ->get();

        $attendanceQuery = new ClassAttendanceSummary;
        $classRows = $classes->map(function (SchoolClass $class) use ($assignmentsByClass, $attendanceQuery, $period, $schoolDays) {
            $assignment = $assignmentsByClass->get($class->id);

            return [
                'class' => $class,
                'subject_teacher' => $assignment?->teacher,
                'attendance' => $attendanceQuery->forClass($class->id, $period->start, $period->end, $schoolDays),
            ];
        });

        $teachers = $assignments->pluck('teacher')
            ->filter()
            ->unique('id')
            ->values();

        $schoolDaysCount = SchoolDays::countBetween($period->start, $period->end, $schoolDays);

        return [
            'classes' => $classRows,
            'teachers' => $teachers,
            'students' => $classes->sum('students_count'),
            'school_days' => $schoolDaysCount,
            'unassigned_classes' => $classRows->filter(fn ($row) => $row['subject_teacher'] === null)->count(),
        ];
    }
}
