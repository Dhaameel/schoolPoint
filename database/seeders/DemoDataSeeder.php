<?php

namespace Database\Seeders;

use App\Actions\Students\CreateStudentAction;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo data cannot be seeded in production.');
        }

        $classes = collect(['Class 1', 'Class 2', 'Class 3'])
            ->map(fn ($name) => SchoolClass::firstOrCreate(['name' => $name], ['status' => 'active']));

        $subjects = collect(['Mathematics', 'English', 'Science', 'Social Studies', 'Religious Education'])
            ->map(fn ($name) => Subject::firstOrCreate(['name' => $name]));

        $teacherNames = [
            ['name' => 'Amina Yusuf', 'username' => 'teacher1', 'phone' => '0611000001'],
            ['name' => 'Hodan Ali', 'username' => 'teacher2', 'phone' => '0611000002'],
            ['name' => 'Mohamed Omar', 'username' => 'teacher3', 'phone' => '0611000003'],
            ['name' => 'Sahra Abdi', 'username' => 'teacher4', 'phone' => '0611000004'],
        ];

        $teachers = collect($teacherNames)->map(function ($data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'role' => 'teacher',
                    'must_change_password' => false,
                    'is_active' => true,
                ]
            );

            return Teacher::firstOrCreate(
                ['user_id' => $user->id],
                ['phone' => $data['phone'], 'status' => 'active']
            );
        });

        foreach ($classes as $index => $class) {
            $class->update(['class_teacher_id' => $teachers[$index]->id]);
        }

        foreach ($classes as $class) {
            foreach ($subjects as $index => $subject) {
                ClassSubject::firstOrCreate(
                    ['class_id' => $class->id, 'subject_id' => $subject->id],
                    ['teacher_id' => $teachers[$index % $teachers->count()]->id]
                );
            }
        }

        if (Student::query()->exists()) {
            return;
        }

        $firstNames = ['Amina', 'Hodan', 'Mohamed', 'Sahra', 'Ali', 'Fatima', 'Omar', 'Khadija', 'Yusuf', 'Halima'];
        $lastNames = ['Yusuf', 'Ali', 'Omar', 'Abdi', 'Hassan', 'Ahmed', 'Farah', 'Warsame'];
        $action = app(CreateStudentAction::class);

        for ($i = 1; $i <= 30; $i++) {
            $class = $classes[($i - 1) % $classes->count()];

            $action->handle([
                'first_name' => $firstNames[($i - 1) % count($firstNames)],
                'last_name' => $lastNames[($i - 1) % count($lastNames)],
                'gender' => $i % 2 === 0 ? 'Female' : 'Male',
                'guardian_name' => 'Guardian '.$i,
                'guardian_phone' => '06120000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'class_id' => $class->id,
            ]);
        }
    }
}
