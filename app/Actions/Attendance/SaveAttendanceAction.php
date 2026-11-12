<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class SaveAttendanceAction
{
    public function handle(SchoolClass $class, string $date, array $statuses, ?string $note, User $marker): array
    {
        return DB::transaction(function () use ($class, $date, $statuses, $note, $marker) {
            $studentIds = Student::where('class_id', $class->id)
                ->where('status', 'active')
                ->whereIn('id', array_keys($statuses))
                ->pluck('id');

            $sameClass = Attendance::whereDate('date', $date)
                ->whereIn('student_id', $studentIds)
                ->where('class_id', $class->id)
                ->get()
                ->keyBy('student_id');

            $otherClass = Attendance::with('schoolClass')
                ->whereDate('date', $date)
                ->whereIn('student_id', $studentIds)
                ->where('class_id', '!=', $class->id)
                ->get()
                ->keyBy('student_id');

            $saved = 0;
            $skippedStudents = [];

            foreach ($studentIds as $studentId) {
                if ($otherClass->has($studentId)) {
                    $skippedStudents[] = $otherClass->get($studentId);
                    continue;
                }

                $status = $statuses[$studentId];
                $existing = $sameClass->get($studentId);
                $oldStatus = $existing?->status;

                $attributes = [
                    'class_id' => $class->id,
                    'status' => $status,
                    'note' => $note,
                    'marked_by' => $marker->id,
                ];

                if ($existing) {
                    $existing->update($attributes);
                } else {
                    Attendance::create(array_merge($attributes, [
                        'student_id' => $studentId,
                        'date' => $date,
                    ]));
                }

                if ($marker->isAdmin() && $date < now()->toDateString() && $oldStatus !== $status) {
                    Audit::record('attendance.updated', 'Attendance changed for a past date.', [
                        'student_id' => $studentId,
                        'class_id' => $class->id,
                        'date' => $date,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                    ]);
                }

                $saved++;
            }

            Audit::record('attendance.marked', 'Attendance saved.', [
                'class_id' => $class->id,
                'date' => $date,
                'saved' => $saved,
                'skipped' => count($skippedStudents),
            ]);

            return [
                'saved' => $saved,
                'skipped' => count($skippedStudents),
                'skipped_students' => $skippedStudents,
            ];
        });
    }
}
