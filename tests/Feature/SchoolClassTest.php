<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
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

    private function student(SchoolClass $class, string $firstName = 'Amina'): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'student_id' => 'SP-2026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => 'Yusuf',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => $sequence,
        ]);
    }

    #[Test]
    public function an_admin_can_list_create_and_update_classes(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacher();

        $this->actingAs($admin)->get('/classes')->assertSuccessful();

        $this->actingAs($admin)->post('/classes', [
            'name' => 'Class 1',
            'code' => 'C1',
            'class_teacher_id' => $teacher->id,
        ])->assertRedirect();

        $class = SchoolClass::firstOrFail();
        $this->assertSame($teacher->id, $class->class_teacher_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'classes.created']);

        $this->actingAs($admin)->put("/classes/{$class->id}", [
            'name' => 'Class One',
            'code' => 'C1',
            'class_teacher_id' => null,
        ])->assertRedirect(route('classes.show', $class));

        $this->assertSame('Class One', $class->fresh()->name);
    }

    #[Test]
    public function a_class_page_lists_the_roster_and_subjects(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Mathematics']);
        ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id]);
        $this->student($class);

        $this->actingAs($admin)->get("/classes/{$class->id}")
            ->assertSuccessful()
            ->assertSee('Mathematics')
            ->assertSee('Amina');
    }

    #[Test]
    public function a_class_with_active_students_cannot_be_archived(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $student = $this->student($class);

        $this->actingAs($admin)->post("/classes/{$class->id}/archive")
            ->assertSessionHas('error');

        $this->assertSame('active', $class->fresh()->status);

        $student->update(['status' => 'graduated']);

        $this->actingAs($admin)->post("/classes/{$class->id}/archive")
            ->assertSessionHas('status');

        $this->assertSame('archived', $class->fresh()->status);
    }

    #[Test]
    public function a_class_teacher_and_subject_teachers_can_be_updated(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacher();
        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $subject = Subject::create(['name' => 'Mathematics']);

        $this->actingAs($admin)->put("/classes/{$class->id}/class-teacher", [
            'class_teacher_id' => $teacher->id,
        ])->assertSessionHas('status');

        $this->assertSame($teacher->id, $class->fresh()->class_teacher_id);

        $this->actingAs($admin)->post("/classes/{$class->id}/subjects", [
            'subject_id' => $subject->id,
            'teacher_id' => null,
        ])->assertSessionHas('status');

        $classSubject = ClassSubject::firstOrFail();
        $this->assertNull($classSubject->teacher_id);

        $this->actingAs($admin)->put("/classes/{$class->id}/subjects/{$classSubject->id}", [
            'teacher_id' => $teacher->id,
        ])->assertSessionHas('status');

        $this->assertSame($teacher->id, $classSubject->fresh()->teacher_id);
    }

    #[Test]
    public function moving_students_requires_a_preview_and_is_audited(): void
    {
        $admin = $this->admin();
        $source = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $target = SchoolClass::create(['name' => 'Class 2', 'status' => 'active']);
        $student = $this->student($source);

        $this->actingAs($admin)->post("/classes/{$source->id}/move-preview", [
            'student_ids' => [$student->id],
            'target_class_id' => $target->id,
        ])->assertSuccessful()
            ->assertSee($student->name)
            ->assertSee('Class 2');

        $this->actingAs($admin)->post("/classes/{$source->id}/move", [
            'student_ids' => [$student->id],
            'target_class_id' => $target->id,
        ])->assertRedirect(route('classes.show', $source));

        $this->assertSame($target->id, $student->fresh()->class_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'classes.students_moved']);
    }

    #[Test]
    public function a_class_cannot_be_moved_to_itself(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $student = $this->student($class);

        $this->actingAs($admin)->post("/classes/{$class->id}/move", [
            'student_ids' => [$student->id],
            'target_class_id' => $class->id,
        ])->assertSessionHas('error');
    }

    #[Test]
    public function a_teacher_cannot_manage_classes(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher->user)->get('/classes')->assertForbidden();
        $this->actingAs($teacher->user)->post('/classes', ['name' => 'Class 1'])->assertForbidden();
    }
}
