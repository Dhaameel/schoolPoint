<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherMyClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function teacher(): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => 'teacher1']);

        return Teacher::create(['user_id' => $user->id, 'status' => 'active']);
    }

    private function student(SchoolClass $class): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'student_id' => 'SP-2026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => $sequence,
        ]);
    }

    #[Test]
    public function a_teacher_sees_only_assigned_classes(): void
    {
        $teacher = $this->teacher();
        $classTeacherClass = SchoolClass::create(['name' => 'Class 1', 'status' => 'active', 'class_teacher_id' => $teacher->id]);
        $subjectTeacherClass = SchoolClass::create(['name' => 'Class 2', 'status' => 'active']);
        $otherClass = SchoolClass::create(['name' => 'Class 3', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Mathematics']);
        ClassSubject::create(['class_id' => $subjectTeacherClass->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        $this->student($classTeacherClass);
        $this->student($otherClass);

        $this->actingAs($teacher->user)->get('/my-classes')
            ->assertSuccessful()
            ->assertSee('Class 1')
            ->assertSee('Class 2')
            ->assertDontSee('Class 3')
            ->assertDontSee('Fees');
    }

    #[Test]
    public function a_teacher_can_open_the_roster_for_an_assigned_class_only(): void
    {
        $teacher = $this->teacher();
        $assigned = SchoolClass::create(['name' => 'Class 1', 'status' => 'active', 'class_teacher_id' => $teacher->id]);
        $other = SchoolClass::create(['name' => 'Class 2', 'status' => 'active']);
        $this->student($assigned);

        $this->actingAs($teacher->user)->get("/my-classes/{$assigned->id}")
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertDontSee('Fees');

        $this->actingAs($teacher->user)->get("/my-classes/{$other->id}")->assertForbidden();
    }

    #[Test]
    public function an_admin_cannot_open_the_teacher_class_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'username' => 'admin']);

        $this->actingAs($admin)->get('/my-classes')->assertForbidden();
    }
}
