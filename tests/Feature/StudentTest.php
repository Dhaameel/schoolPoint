<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'username' => 'admin'.Str::lower(Str::random(6)),
        ]);
    }

    private function classes(): array
    {
        return [
            SchoolClass::create(['name' => 'Class 1', 'status' => 'active']),
            SchoolClass::create(['name' => 'Class 2', 'status' => 'active']),
        ];
    }

    private function payload(SchoolClass $class, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'gender' => 'Female',
            'guardian_name' => 'Hodan Yusuf',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
        ], $overrides);
    }

    #[Test]
    public function an_admin_can_create_a_student_with_an_automatic_id(): void
    {
        $class = $this->classes()[0];

        $this->actingAs($this->admin())->post('/students', $this->payload($class))
            ->assertRedirect();

        $student = Student::firstOrFail();
        $year = now()->year;

        $this->assertSame("SP-{$year}-000001", $student->student_id);
        $this->assertSame('active', $student->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.created']);
    }

    #[Test]
    public function student_ids_are_sequential_and_unique(): void
    {
        $class = $this->classes()[0];
        $admin = $this->admin();

        $this->actingAs($admin)->post('/students', $this->payload($class, ['first_name' => 'Amina']));
        $this->actingAs($admin)->post('/students', $this->payload($class, ['first_name' => 'Hodan']));

        $year = now()->year;
        $ids = Student::orderBy('id')->pluck('student_id');

        $this->assertSame(["SP-{$year}-000001", "SP-{$year}-000002"], $ids->all());
    }

    #[Test]
    public function the_student_list_can_be_searched_and_filtered(): void
    {
        $classes = $this->classes();
        $admin = $this->admin();
        $this->actingAs($admin)->post('/students', $this->payload($classes[0], ['first_name' => 'Amina']));
        $this->actingAs($admin)->post('/students', $this->payload($classes[1], ['first_name' => 'Hodan', 'guardian_phone' => '0699999999']));
        $second = Student::orderByDesc('id')->firstOrFail();
        $second->update(['status' => 'graduated']);

        $this->actingAs($admin)->get('/students?search=Amina')
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertDontSee('Hodan');

        $this->actingAs($admin)->get('/students?search=0699999999')
            ->assertSuccessful()
            ->assertSee('Hodan');

        $this->actingAs($admin)->get('/students?class_id='.$classes[1]->id)
            ->assertSuccessful()
            ->assertSee('Hodan');

        $this->actingAs($admin)->get('/students?status=graduated')
            ->assertSuccessful()
            ->assertSee('Hodan');
    }

    #[Test]
    public function an_admin_can_edit_a_student(): void
    {
        $class = $this->classes()[0];
        $this->actingAs($this->admin())->post('/students', $this->payload($class));
        $student = Student::firstOrFail();

        $this->actingAs($this->admin())->put("/students/{$student->id}", $this->payload($class, [
            'first_name' => 'Amina',
            'last_name' => 'Ali',
            'guardian_name' => 'Hodan Ali',
        ]))->assertRedirect(route('students.show', $student));

        $this->assertSame('Amina Ali', $student->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.updated']);
    }

    #[Test]
    public function moving_a_student_to_another_class_is_audited(): void
    {
        [$source, $target] = $this->classes();
        $this->actingAs($this->admin())->post('/students', $this->payload($source));
        $student = Student::firstOrFail();

        $this->actingAs($this->admin())->post("/students/{$student->id}/move-class", [
            'class_id' => $target->id,
        ])->assertRedirect(route('students.show', $student));

        $this->assertSame($target->id, $student->fresh()->class_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.class_moved']);
    }

    #[Test]
    public function a_student_status_can_be_changed(): void
    {
        $class = $this->classes()[0];
        $this->actingAs($this->admin())->post('/students', $this->payload($class));
        $student = Student::firstOrFail();

        $this->actingAs($this->admin())->post("/students/{$student->id}/status", [
            'status' => 'graduated',
        ])->assertRedirect(route('students.show', $student));

        $this->assertSame('graduated', $student->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.status_changed']);
    }

    #[Test]
    public function a_teacher_cannot_manage_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'username' => 'teacher1']);

        $this->actingAs($teacher)->get('/students')->assertForbidden();
        $this->actingAs($teacher)->post('/students', [])->assertForbidden();
    }
}
