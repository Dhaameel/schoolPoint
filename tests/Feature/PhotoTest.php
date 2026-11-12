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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'admin']);
    }

    private function teacher(string $username = 'teacher1'): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => $username]);

        return Teacher::create(['user_id' => $user->id, 'status' => 'active']);
    }

    private function classWithTeacher(Teacher $teacher): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'Class 1',
            'status' => 'active',
            'class_teacher_id' => $teacher->id,
        ]);
    }

    #[Test]
    public function a_teacher_photo_is_stored_privately_and_served_through_an_authorized_route(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/teachers', [
            'name' => 'Amina Yusuf',
            'username' => 'amina',
            'photo' => UploadedFile::fake()->image('photo.png', 300, 300),
        ])->assertRedirect(route('teachers.slip'));

        $teacher = Teacher::firstOrFail();

        $this->assertNotNull($teacher->photo_path);
        $this->assertTrue(Storage::disk('local')->exists($teacher->photo_path));
        $this->assertFalse(Storage::disk('public')->exists($teacher->photo_path));
        $this->assertSame(IMAGETYPE_JPEG, getimagesize(Storage::disk('local')->path($teacher->photo_path))[2]);

        $this->actingAs($admin)->get(route('photos.teachers.show', $teacher))->assertSuccessful();

        $teacher->user->forceFill(['must_change_password' => false])->save();
        $this->actingAs($teacher->user)->get(route('photos.teachers.show', $teacher))->assertSuccessful();
    }

    #[Test]
    public function a_student_photo_is_private_and_only_assigned_teachers_can_view_it(): void
    {
        $admin = $this->admin();
        $assigned = $this->teacher('teacher1');
        $other = $this->teacher('teacher2');
        $class = $this->classWithTeacher($assigned);

        $this->actingAs($admin)->post('/students', [
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'photo' => UploadedFile::fake()->image('student.jpg', 400, 400),
        ])->assertRedirect();

        $student = Student::firstOrFail();

        $this->assertTrue(Storage::disk('local')->exists($student->photo_path));
        $this->assertFalse(Storage::disk('public')->exists($student->photo_path));
        $this->actingAs($admin)->get(route('photos.students.show', $student))->assertSuccessful();
        $this->actingAs($assigned->user)->get(route('photos.students.show', $student))->assertSuccessful();
        $this->actingAs($other->user)->get(route('photos.students.show', $student))->assertForbidden();
    }

    #[Test]
    public function a_subject_teacher_can_view_a_student_photo(): void
    {
        $teacher = $this->teacher();
        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Mathematics']);
        ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        $student = Student::create([
            'student_id' => 'SP-2026-000001',
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => 1,
            'photo_path' => 'photos/students/test.jpg',
        ]);
        Storage::disk('local')->put($student->photo_path, 'fake');

        $this->actingAs($teacher->user)->get(route('photos.students.show', $student))->assertSuccessful();
    }

    #[Test]
    public function an_invalid_photo_is_rejected(): void
    {
        $this->actingAs($this->admin())->post('/teachers', [
            'name' => 'Amina Yusuf',
            'username' => 'amina',
            'photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }
}
