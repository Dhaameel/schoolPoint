<?php

namespace App\Actions\Students;

use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentIdCounter;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class CreateStudentAction
{
    public function handle(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $year = (int) now()->year;

            $counter = StudentIdCounter::firstOrCreate(
                ['year' => $year],
                ['last_sequence' => 0]
            );

            $counter = StudentIdCounter::whereKey($counter->id)->lockForUpdate()->firstOrFail();
            $counter->increment('last_sequence');
            $counter->refresh();

            $prefix = Setting::get('student_id_prefix', 'SP');
            $studentId = sprintf('%s-%d-%06d', $prefix, $year, $counter->last_sequence);

            $student = Student::create(array_merge($data, [
                'student_id' => $studentId,
                'student_year' => $year,
                'student_sequence' => $counter->last_sequence,
                'status' => 'active',
            ]));

            Audit::record('students.created', 'Student created.', [
                'student_id' => $student->student_id,
                'class_id' => $student->class_id,
            ]);

            return $student;
        });
    }
}
