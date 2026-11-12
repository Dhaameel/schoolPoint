<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00', 'Africa/Mogadishu'));
        $this->admin = User::factory()->create(['role' => 'admin', 'username' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function teacher(string $username = 'teacher1'): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => $username]);

        return Teacher::create(['user_id' => $user->id, 'status' => 'active']);
    }

    private function class(string $name = 'Class 1'): SchoolClass
    {
        return SchoolClass::create(['name' => $name, 'status' => 'active']);
    }

    private function student(SchoolClass $class, string $name = 'Amina'): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'student_id' => 'SP-2026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => $name,
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => $sequence,
        ]);
    }

    private function fee(
        Student $student,
        string $amount = '100.00',
        string $currency = 'USD',
        string $billedAt = '2026-11-05',
        string $amountPaid = '0.00',
        string $status = 'unpaid'
    ): Fee {
        return Fee::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'title' => 'Tuition',
            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'currency' => $currency,
            'status' => $status,
            'billed_at' => Carbon::parse($billedAt)->setTime(9, 0),
        ]);
    }

    private function payment(Fee $fee, string $amount = '40.00', string $paidAt = '2026-11-06', string $type = 'payment'): FeePayment
    {
        return FeePayment::create([
            'fee_id' => $fee->id,
            'reference' => 'REF-'.Str::upper(Str::random(10)),
            'receipt_number' => 'RCPT-TEST-'.Str::upper(Str::random(10)),
            'amount' => $amount,
            'type' => $type,
            'paid_at' => Carbon::parse($paidAt)->setTime(10, 0),
            'created_by' => $this->admin->id,
        ]);
    }

    private function attendance(Student $student, string $date, string $status = 'present', ?User $marker = null): Attendance
    {
        return Attendance::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'date' => $date,
            'status' => $status,
            'marked_by' => ($marker ?? $this->admin)->id,
        ]);
    }

    #[Test]
    public function the_reports_index_and_all_three_report_pages_render(): void
    {
        $this->teacher();

        foreach (['/reports', '/reports/students', '/reports/teachers', '/reports/classes', '/reports/subjects'] as $uri) {
            $this->actingAs($this->admin)->get($uri)->assertSuccessful();
        }
    }

    #[Test]
    public function teachers_cannot_open_reports(): void
    {
        $teacher = $this->teacher();

        foreach ([
            '/reports',
            '/reports/students',
            '/reports/teachers',
            '/reports/classes',
            '/reports/subjects',
            '/reports/students/csv',
            '/reports/classes/print',
            '/reports/school/csv',
            '/reports/subjects/print',
        ] as $uri) {
            $this->actingAs($teacher->user)->get($uri)->assertForbidden();
        }
    }

    #[Test]
    public function a_custom_range_must_be_valid(): void
    {
        $this->actingAs($this->admin)->get('/reports/students?range=custom')
            ->assertSessionHasErrors(['start_date', 'end_date']);

        $this->actingAs($this->admin)->get('/reports/students?range=custom&start_date=2026-11-10&end_date=2026-11-01')
            ->assertSessionHasErrors('end_date');

        $this->actingAs($this->admin)->get('/reports/students?range=invalid')
            ->assertSessionHasErrors('range');
    }

    #[Test]
    public function the_student_report_respects_the_date_range(): void
    {
        $class = $this->class();
        $student = $this->student($class);

        $novemberFee = $this->fee($student, '100.00', 'USD', '2026-11-05', '40.00', 'partial');
        $octoberFee = $this->fee($student, '50.00', 'USD', '2026-10-01');

        $this->payment($novemberFee, '40.00', '2026-11-06');
        $this->payment($octoberFee, '10.00', '2026-10-02');

        $this->attendance($student, '2026-11-09', 'present');
        $this->attendance($student, '2026-11-10', 'absent');
        $this->attendance($student, '2026-10-01', 'present');

        $november = $this->actingAs($this->admin)->get('/reports/students?student_id='.$student->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30');
        $november->assertSuccessful()->assertSee('Amina');
        $november->assertViewHas('report', function (array $report) {
            return $report['fees_billed'] === ['USD' => '100.00']
                && $report['fees_paid'] === ['USD' => '40.00']
                && $report['balance'] === ['USD' => '110.00']
                && $report['attendance']['present'] === 1
                && $report['attendance']['absent'] === 1
                && $report['attendance']['school_days'] === 21;
        });

        $october = $this->actingAs($this->admin)->get('/reports/students?student_id='.$student->id.'&range=custom&start_date=2026-10-01&end_date=2026-10-31');
        $october->assertViewHas('report', function (array $report) {
            return $report['fees_billed'] === ['USD' => '50.00']
                && $report['fees_paid'] === ['USD' => '10.00']
                && $report['attendance']['present'] === 1;
        });
    }

    #[Test]
    public function the_student_report_print_and_csv_show_the_same_figures(): void
    {
        $student = $this->student($this->class());
        $fee = $this->fee($student, '100.00', 'USD', '2026-11-05', '40.00', 'partial');
        $this->payment($fee, '40.00', '2026-11-06');
        $this->attendance($student, '2026-11-09', 'present');

        $query = 'student_id='.$student->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30';

        $this->actingAs($this->admin)->get('/reports/students/print?'.$query)
            ->assertSuccessful()
            ->assertSee('100.00')
            ->assertSee('40.00')
            ->assertSee('60.00');

        $response = $this->actingAs($this->admin)->get('/reports/students/csv?'.$query)->assertSuccessful();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Fees billed', $csv);
        $this->assertStringContainsString('100.00', $csv);
        $this->assertStringContainsString('40.00', $csv);
        $this->assertStringContainsString('60.00', $csv);
        $this->assertStringContainsString('Present', $csv);
    }

    #[Test]
    public function the_school_report_aggregates_attendance_and_fees_from_real_records(): void
    {
        $firstClass = $this->class();
        $secondClass = $this->class('Class 2');
        $first = $this->student($firstClass, 'Amina');
        $second = $this->student($firstClass, 'Hodan');
        $third = $this->student($secondClass, 'Ali');

        $this->attendance($first, '2026-11-09', 'present');
        $this->attendance($second, '2026-11-09', 'absent');
        $this->attendance($third, '2026-11-09', 'present');

        $usdFee = $this->fee($first, '100.00', 'USD', '2026-11-05', '40.00', 'partial');
        $this->fee($third, '50.00', 'EUR', '2026-11-05');
        $this->payment($usdFee, '40.00', '2026-11-06');

        $response = $this->actingAs($this->admin)
            ->get('/reports?range=custom&start_date=2026-11-01&end_date=2026-11-30');

        $response->assertSuccessful();
        $response->assertViewHas('report', function (array $report) {
            return $report['students_active'] === 3
                && $report['classes_total'] === 2
                && $report['attendance']['present'] === 2
                && $report['attendance']['absent'] === 1
                && $report['fees_billed'] === ['USD' => '100.00', 'EUR' => '50.00']
                && $report['fees_collected'] === ['USD' => '40.00']
                && $report['outstanding'] === ['USD' => '60.00', 'EUR' => '50.00']
                && $report['class_rows']->count() === 2;
        });

        $this->actingAs($this->admin)
            ->get('/reports/school/print?range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->assertSuccessful()
            ->assertSee('USD')
            ->assertSee('60.00');

        $csv = $this->actingAs($this->admin)
            ->get('/reports/school/csv?range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->streamedContent();

        $this->assertStringContainsString('Collected in range', $csv);
        $this->assertStringContainsString('60.00', $csv);
    }

    #[Test]
    public function the_subject_report_shows_classes_teachers_and_class_attendance(): void
    {
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = $this->teacher();
        $firstClass = $this->class();
        $secondClass = $this->class('Class 2');
        $first = $this->student($firstClass, 'Amina');
        $second = $this->student($firstClass, 'Hodan');
        $third = $this->student($secondClass, 'Ali');

        $firstClass->update(['class_teacher_id' => $teacher->id]);
        ClassSubject::create(['class_id' => $firstClass->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        ClassSubject::create(['class_id' => $secondClass->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        $this->attendance($first, '2026-11-09', 'present');
        $this->attendance($second, '2026-11-10', 'absent');
        $this->attendance($third, '2026-11-09', 'present');

        $response = $this->actingAs($this->admin)
            ->get('/reports/subjects?subject_id='.$subject->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30');

        $response->assertSuccessful();
        $response->assertViewHas('report', function (array $report) {
            return $report['classes']->count() === 2
                && $report['teachers']->count() === 1
                && $report['students'] === 3
                && $report['unassigned_classes'] === 0;
        });

        $this->actingAs($this->admin)
            ->get('/reports/subjects/print?subject_id='.$subject->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->assertSuccessful()
            ->assertSee('Mathematics')
            ->assertSee('MATH');

        $csv = $this->actingAs($this->admin)
            ->get('/reports/subjects/csv?subject_id='.$subject->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->streamedContent();

        $this->assertStringContainsString('Mathematics', $csv);
        $this->assertStringContainsString('Subject teacher', $csv);
    }

    #[Test]
    public function the_student_report_nets_reversals_in_payments(): void
    {
        $student = $this->student($this->class());
        $fee = $this->fee($student, '100.00', 'USD', '2026-11-05');

        $this->payment($fee, '100.00', '2026-11-06', 'payment');
        $this->payment($fee, '100.00', '2026-11-07', 'reversal');

        $response = $this->actingAs($this->admin)
            ->get('/reports/students?student_id='.$student->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30');

        $response->assertViewHas('report', function (array $report) {
            return $report['fees_paid'] === ['USD' => '0.00']
                && $report['balance'] === ['USD' => '100.00'];
        });
    }

    #[Test]
    public function the_teacher_report_counts_only_attendance_marked_by_that_teacher_in_range(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = $this->teacher('teacher2');
        $class = $this->class();
        $subject = Subject::create(['name' => 'Mathematics']);
        $otherClass = $this->class('Class 2');

        $class->update(['class_teacher_id' => $teacher->id]);
        ClassSubject::create([
            'class_id' => $otherClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = $this->student($class);
        $otherStudent = $this->student($class, 'Hodan');
        $this->attendance($student, '2026-11-09', 'present', $teacher->user);
        $this->attendance($student, '2026-11-10', 'absent', $teacher->user);
        $this->attendance($student, '2026-10-01', 'late', $teacher->user);
        $this->attendance($otherStudent, '2026-11-09', 'present', $otherTeacher->user);

        $response = $this->actingAs($this->admin)
            ->get('/reports/teachers?teacher_id='.$teacher->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30');

        $response->assertSuccessful();
        $response->assertViewHas('report', function (array $report) {
            return $report['class_teacher_classes']->count() === 1
                && $report['subject_assignments']->count() === 1
                && $report['attendance'] === [
                    'total' => 2,
                    'present' => 1,
                    'absent' => 1,
                    'late' => 0,
                    'days' => 2,
                    'classes' => 1,
                ];
        });

        $this->actingAs($this->admin)->get('/reports/teachers/print?teacher_id='.$teacher->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->assertSuccessful()
            ->assertSee('Mathematics')
            ->assertSee('Records marked');

        $csv = $this->actingAs($this->admin)
            ->get('/reports/teachers/csv?teacher_id='.$teacher->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->streamedContent();

        $this->assertStringContainsString('Records marked', $csv);
        $this->assertStringContainsString('Class teacher', $csv);
    }

    #[Test]
    public function the_class_report_shows_attendance_collections_outstanding_and_owing_students(): void
    {
        $class = $this->class();
        $first = $this->student($class, 'Amina');
        $second = $this->student($class, 'Hodan');

        $this->attendance($first, '2026-11-09', 'present');
        $this->attendance($first, '2026-11-10', 'absent');
        $this->attendance($second, '2026-11-09', 'present');
        $this->attendance($first, '2026-10-01', 'present');

        $usdFee = $this->fee($first, '100.00', 'USD', '2026-11-05', '40.00', 'partial');
        $eurFee = $this->fee($second, '50.00', 'EUR', '2026-11-05', '20.00', 'partial');
        $oldPaidFee = $this->fee($first, '20.00', 'USD', '2026-10-01', '20.00', 'paid');
        $this->payment($usdFee, '40.00', '2026-11-06');
        $this->payment($eurFee, '20.00', '2026-11-07');
        $this->payment($oldPaidFee, '20.00', '2026-10-05');

        $response = $this->actingAs($this->admin)
            ->get('/reports/classes?class_id='.$class->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30');

        $response->assertSuccessful();
        $response->assertViewHas('report', function (array $report) {
            return $report['attendance']['present'] === 2
                && $report['attendance']['absent'] === 1
                && $report['attendance']['school_days'] === 21
                && $report['collected']['USD'] === '40.00'
                && $report['collected']['EUR'] === '20.00'
                && $report['outstanding']['USD'] === '60.00'
                && $report['outstanding']['EUR'] === '30.00'
                && $report['owing']->count() === 2;
        });

        $this->actingAs($this->admin)->get('/reports/classes/print?class_id='.$class->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertSee('Hodan')
            ->assertSee('40.00')
            ->assertSee('60.00');

        $csv = $this->actingAs($this->admin)
            ->get('/reports/classes/csv?class_id='.$class->id.'&range=custom&start_date=2026-11-01&end_date=2026-11-30')
            ->streamedContent();

        $this->assertStringContainsString('Collected in range', $csv);
        $this->assertStringContainsString('40.00', $csv);
        $this->assertStringContainsString('60.00', $csv);
        $this->assertStringContainsString('30.00', $csv);
    }
}
