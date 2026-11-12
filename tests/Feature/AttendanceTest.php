<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Queries\Attendance\ClassAttendanceSummary;
use App\Queries\Attendance\StudentAttendanceSummary;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'admin'.Str::lower(Str::random(6))]);
    }

    private function teacher(string $username = 'teacher1'): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => $username]);

        return Teacher::create(['user_id' => $user->id, 'status' => 'active']);
    }

    private function class(string $name = 'Class 1', ?Teacher $teacher = null): SchoolClass
    {
        return SchoolClass::create([
            'name' => $name,
            'status' => 'active',
            'class_teacher_id' => $teacher?->id,
        ]);
    }

    private function student(SchoolClass $class, string $name = 'Amina', string $guardianPhone = '0612345678'): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'student_id' => 'SP-2026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => $name,
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => $guardianPhone,
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => $sequence,
        ]);
    }

    private function postAttendance(SchoolClass $class, string $date, array $statuses)
    {
        return $this->actingAs($this->admin())->post('/attendance/take', [
            'class_id' => $class->id,
            'date' => $date,
            'statuses' => $statuses,
            'note' => 'Daily note',
        ]);
    }

    #[Test]
    public function an_admin_can_take_attendance_and_repeat_saves_update_one_row(): void
    {
        $class = $this->class();
        $student = $this->student($class);
        $date = now()->toDateString();

        $this->postAttendance($class, $date, [$student->id => 'present'])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
        $this->postAttendance($class, $date, [$student->id => 'absent'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSame(1, Attendance::where('student_id', $student->id)->whereDate('date', $date)->count());
        $this->assertSame('absent', Attendance::firstOrFail()->status);
        $this->assertSame($class->id, Attendance::firstOrFail()->class_id);
    }

    #[Test]
    public function admin_changes_to_a_past_date_are_audited_with_old_and_new_status(): void
    {
        $class = $this->class();
        $student = $this->student($class);
        $date = now()->subDay()->toDateString();

        $this->postAttendance($class, $date, [$student->id => 'present']);
        $this->postAttendance($class, $date, [$student->id => 'late']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.updated',
        ]);

        $log = AuditLog::where('action', 'attendance.updated')->latest('id')->firstOrFail();
        $this->assertSame('present', $log->metadata['old_status']);
        $this->assertSame('late', $log->metadata['new_status']);
    }

    #[Test]
    public function a_moved_student_is_not_duplicated_and_keeps_the_old_class_attendance(): void
    {
        $admin = $this->admin();
        $source = $this->class('Class 1');
        $target = $this->class('Class 2');
        $student = $this->student($source);
        $date = now()->toDateString();

        $this->actingAs($admin)->post('/attendance/take', [
            'class_id' => $source->id,
            'date' => $date,
            'statuses' => [$student->id => 'present'],
        ])->assertSessionHas('status');

        $student->update(['class_id' => $target->id]);

        $this->actingAs($admin)->post('/attendance/take', [
            'class_id' => $target->id,
            'date' => $date,
            'statuses' => [$student->id => 'present'],
        ])->assertSessionHas('warning');

        $this->assertSame(1, Attendance::where('student_id', $student->id)->whereDate('date', $date)->count());
        $this->assertSame($source->id, Attendance::firstOrFail()->class_id);
    }

    #[Test]
    public function a_teacher_can_mark_only_their_assigned_class_today(): void
    {
        config(['app.timezone' => 'Africa/Mogadishu']);
        date_default_timezone_set('Africa/Mogadishu');
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00', 'Africa/Mogadishu'));

        $teacher = $this->teacher();
        $class = $this->class('Class 1', $teacher);
        $other = $this->class('Class 2');
        $student = $this->student($class);
        $date = now()->toDateString();

        $this->actingAs($teacher->user)->get("/teacher/attendance/{$class->id}/take")->assertSuccessful();
        $this->actingAs($teacher->user)->get("/teacher/attendance/{$other->id}/take")->assertForbidden();

        $this->actingAs($teacher->user)->post("/teacher/attendance/{$class->id}", [
            'statuses' => [$student->id => 'present'],
        ])->assertRedirect(route('teacher.attendance'));

        $this->actingAs($teacher->user)->post("/teacher/attendance/{$class->id}", [
            'date' => now()->subDay()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ])->assertForbidden();

        $policy = new AttendancePolicy;
        $this->assertTrue($policy->editDate($teacher->user, $class, $date));
        $this->assertFalse($policy->editDate($teacher->user, $class, now()->subDay()->toDateString()));
        $this->assertFalse($policy->markClass($teacher->user, $other));
    }

    #[Test]
    public function left_students_are_not_on_the_roster_but_stay_in_old_reports(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $left = $this->student($class, 'Fadumo');
        $active = $this->student($class, 'Amina');

        Attendance::create([
            'student_id' => $left->id,
            'class_id' => $class->id,
            'date' => '2026-11-09',
            'status' => 'present',
            'marked_by' => $admin->id,
        ]);

        $left->update(['status' => 'left']);

        $this->actingAs($admin)
            ->get('/attendance/take?class_id='.$class->id.'&date=2026-11-10')
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertDontSee('Fadumo');

        $this->actingAs($admin)
            ->get('/attendance?class_id='.$class->id.'&month=2026-11')
            ->assertSuccessful()
            ->assertSee('Fadumo')
            ->assertSee('Amina');

        $this->assertSame(1, Attendance::where('student_id', $left->id)->count());
    }

    #[Test]
    public function the_teacher_today_rule_holds_across_midnight_in_the_configured_timezone(): void
    {
        $teacher = $this->teacher();
        $class = $this->class('Class 1', $teacher);
        $policy = new AttendancePolicy;

        Setting::put('timezone', 'Africa/Mogadishu');
        $timezone = Setting::get('timezone');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        Carbon::setTestNow(Carbon::parse('2026-11-12 23:59:59', 'Africa/Mogadishu'));

        $this->assertTrue($policy->editDate($teacher->user, $class, '2026-11-12'));
        $this->assertFalse($policy->editDate($teacher->user, $class, '2026-11-11'));

        Carbon::setTestNow(Carbon::parse('2026-11-13 00:00:01', 'Africa/Mogadishu'));

        $this->assertFalse($policy->editDate($teacher->user, $class, '2026-11-12'));
        $this->assertTrue($policy->editDate($teacher->user, $class, '2026-11-13'));
    }

    #[Test]
    public function school_days_drive_the_attendance_percentage(): void
    {
        Setting::put('school_days', json_encode([1, 2, 3, 4, 5]));
        $class = $this->class();
        $student = $this->student($class);

        Attendance::create(['student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-11-09', 'status' => 'present', 'marked_by' => $this->admin()->id]);
        Attendance::create(['student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-11-10', 'status' => 'absent', 'marked_by' => $this->admin()->id]);
        Attendance::create(['student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-11-14', 'status' => 'present', 'marked_by' => $this->admin()->id]);

        $summary = (new StudentAttendanceSummary)->forStudent(
            $student,
            Carbon::parse('2026-11-09'),
            Carbon::parse('2026-11-15'),
            [1, 2, 3, 4, 5]
        );

        $this->assertSame(1, $summary['present']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(5, $summary['school_days']);
        $this->assertSame(20.0, $summary['percentage']);

        $classSummary = (new ClassAttendanceSummary)->forClass($class->id, Carbon::parse('2026-11-09'), Carbon::parse('2026-11-15'), [1, 2, 3, 4, 5]);
        $this->assertSame(5, $classSummary['school_days']);
        $this->assertSame(20.0, $classSummary['percentage']);
    }

    #[Test]
    public function the_admin_monthly_grid_contains_students_and_marks(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $student = $this->student($class);
        Attendance::create(['student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-11-09', 'status' => 'present', 'marked_by' => $admin->id]);

        $grid = $this->actingAs($admin)->get('/attendance?class_id='.$class->id.'&month=2026-11')
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertSee('bi-check-lg')
            ->assertSee('non-school')
            ->assertSee('Attendance trend');

        $grid->assertViewHas('dailyCounts', fn (array $counts) => $counts['2026-11-09']['present'] === 1);

        $this->actingAs($admin)->get('/attendance/print?class_id='.$class->id.'&month=2026-11')
            ->assertSuccessful()
            ->assertSee('Amina');
    }

    #[Test]
    public function the_attendance_csv_escapes_dangerous_cells(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $student = $this->student($class, '=SUM(A1)');

        Attendance::create(['student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-11-09', 'status' => 'present', 'marked_by' => $admin->id]);

        $response = $this->actingAs($admin)->get('/attendance/csv?class_id='.$class->id.'&month=2026-11');
        $response->assertSuccessful();

        $this->assertStringContainsString("'=SUM(A1)", $response->streamedContent());
    }
}
