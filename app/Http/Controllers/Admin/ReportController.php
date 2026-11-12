<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRangeRequest;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Queries\Reports\ClassReportQuery;
use App\Queries\Reports\SchoolReportQuery;
use App\Queries\Reports\StudentReportQuery;
use App\Queries\Reports\SubjectReportQuery;
use App\Queries\Reports\TeacherReportQuery;
use App\Support\Csv;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(ReportRangeRequest $request)
    {
        return view('admin.reports.index', $this->schoolContext($request));
    }

    public function schoolPrint(ReportRangeRequest $request)
    {
        return view('admin.reports.print.school', $this->schoolContext($request));
    }

    public function schoolCsv(ReportRangeRequest $request)
    {
        $data = $this->schoolContext($request);
        $period = $data['period'];
        $report = $data['report'];

        $rows = [$this->csvHeader()];
        $rows[] = [__('messages.report_range'), __('messages.from'), '', '', $period->startDate()];
        $rows[] = [__('messages.report_range'), __('messages.to'), '', '', $period->endDate()];
        $rows[] = [__('messages.report_profile'), __('messages.student_count'), '', '', $report['students_active'].' / '.$report['students_total']];
        $rows[] = [__('messages.report_profile'), __('messages.classes'), '', '', $report['classes_active'].' / '.$report['classes_total']];
        $rows[] = [__('messages.report_profile'), __('messages.teachers'), '', '', $report['teachers_active'].' / '.$report['teachers_total']];
        $rows[] = [__('messages.report_profile'), __('messages.subjects'), '', '', $report['subjects_total']];

        foreach ([
            'present' => __('messages.present'),
            'absent' => __('messages.absent'),
            'late' => __('messages.late'),
            'school_days' => __('messages.school_days'),
        ] as $key => $label) {
            $value = $key === 'school_days' ? $report['school_days'] : $report['attendance'][$key];
            $rows[] = [__('messages.report_attendance'), $label, '', '', $value];
        }

        $rows[] = [__('messages.report_attendance'), __('messages.percentage_present'), '', '', number_format($report['attendance']['percentage'], 1).'%'];

        foreach ($this->currencies($report['fees_billed'], $report['fees_collected'], $report['outstanding']) as $currency) {
            $rows[] = [__('messages.report_fees'), __('messages.report_fees_billed'), '', $currency, $report['fees_billed'][$currency] ?? '0.00'];
            $rows[] = [__('messages.report_fees'), __('messages.report_collected'), '', $currency, $report['fees_collected'][$currency] ?? '0.00'];
            $rows[] = [__('messages.report_fees'), __('messages.report_outstanding'), '', $currency, $report['outstanding'][$currency] ?? '0.00'];
        }

        foreach (['unpaid', 'partial', 'paid', 'void'] as $status) {
            $rows[] = [__('messages.report_fees'), __('messages.'.$this->feeStatusLabel($status)), '', '', (int) ($report['fee_status_counts'][$status] ?? 0)];
        }

        foreach ($report['class_rows'] as $row) {
            $rows[] = [__('messages.classes'), __('messages.percentage_present'), $row['class']->name, '', $row['percentage'].'%'];
            $rows[] = [__('messages.classes'), __('messages.student_count'), $row['class']->name, '', $row['students']];
        }

        return $this->csvDownload('school-report-'.$period->startDate().'-'.$period->endDate().'.csv', $rows);
    }

    public function students(ReportRangeRequest $request)
    {
        $data = $this->studentContext($request);
        $students = Student::with('schoolClass')->orderBy('student_id')->get();

        return view('admin.reports.students', array_merge($data, compact('students')));
    }

    public function studentPrint(ReportRangeRequest $request)
    {
        return view('admin.reports.print.student', $this->studentContext($request));
    }

    public function studentCsv(ReportRangeRequest $request)
    {
        $data = $this->studentContext($request);
        $student = $data['student'];
        $period = $data['period'];
        $report = $data['report'];

        $rows = [$this->csvHeader()];
        $rows[] = [__('messages.report_profile'), __('messages.student_name'), '', '', $student?->name ?? __('messages.report_no_student')];
        $rows[] = [__('messages.report_profile'), __('messages.student_id'), '', '', $student?->student_id ?? ''];
        $rows[] = [__('messages.report_profile'), __('messages.class'), '', '', $student?->schoolClass?->name ?? ''];
        $rows[] = [__('messages.report_profile'), __('messages.status'), '', '', $student ? __('messages.'.$student->status) : ''];
        $rows[] = [__('messages.report_range'), __('messages.from'), '', '', $period->startDate()];
        $rows[] = [__('messages.report_range'), __('messages.to'), '', '', $period->endDate()];

        if ($report) {
            $currencies = $this->currencies($report['fees_billed'], $report['fees_paid'], $report['balance']);

            foreach ($currencies as $currency) {
                $rows[] = [__('messages.report_fees'), __('messages.report_fees_billed'), '', $currency, $report['fees_billed'][$currency] ?? '0.00'];
                $rows[] = [__('messages.report_fees'), __('messages.report_payments_in_range'), '', $currency, $report['fees_paid'][$currency] ?? '0.00'];
                $rows[] = [__('messages.report_fees'), __('messages.report_balance_now'), '', $currency, $report['balance'][$currency] ?? '0.00'];
            }

            if ($currencies === []) {
                $rows[] = [__('messages.report_fees'), __('messages.report_no_fees_in_range'), '', '', ''];
            }

            foreach ([
                'present' => __('messages.present'),
                'absent' => __('messages.absent'),
                'late' => __('messages.late'),
                'school_days' => __('messages.school_days'),
            ] as $key => $label) {
                $rows[] = [__('messages.report_attendance'), $label, '', '', $report['attendance'][$key]];
            }

            $rows[] = [__('messages.report_attendance'), __('messages.percentage_present'), '', '', number_format($report['attendance']['percentage'], 1).'%'];
        }

        return $this->csvDownload('student-report-'.($student?->student_id ?? 'none').'-'.$period->startDate().'-'.$period->endDate().'.csv', $rows);
    }

    public function teachers(ReportRangeRequest $request)
    {
        $data = $this->teacherContext($request);
        $teachers = Teacher::with('user')->orderBy('id')->get();

        return view('admin.reports.teachers', array_merge($data, compact('teachers')));
    }

    public function teacherPrint(ReportRangeRequest $request)
    {
        return view('admin.reports.print.teacher', $this->teacherContext($request));
    }

    public function teacherCsv(ReportRangeRequest $request)
    {
        $data = $this->teacherContext($request);
        $teacher = $data['teacher'];
        $period = $data['period'];
        $report = $data['report'];

        $rows = [$this->csvHeader()];
        $rows[] = [__('messages.report_profile'), __('messages.teacher_name'), '', '', $teacher?->user?->name ?? __('messages.report_no_teacher')];
        $rows[] = [__('messages.report_profile'), __('messages.teacher_username'), '', '', $teacher?->user?->username ?? ''];
        $rows[] = [__('messages.report_profile'), __('messages.status'), '', '', $teacher ? __('messages.'.$teacher->status) : ''];
        $rows[] = [__('messages.report_range'), __('messages.from'), '', '', $period->startDate()];
        $rows[] = [__('messages.report_range'), __('messages.to'), '', '', $period->endDate()];

        if ($report) {
            foreach ($report['class_teacher_classes'] as $class) {
                $rows[] = [__('messages.report_assignments'), __('messages.class_teacher'), $class->name, '', ''];
            }

            foreach ($report['subject_assignments'] as $assignment) {
                $rows[] = [
                    __('messages.report_assignments'),
                    __('messages.subject_teacher'),
                    ($assignment->schoolClass?->name ?? '-').' / '.($assignment->subject?->name ?? '-'),
                    '',
                    '',
                ];
            }

            if ($report['class_teacher_classes']->isEmpty() && $report['subject_assignments']->isEmpty()) {
                $rows[] = [__('messages.report_assignments'), __('messages.report_no_assignments'), '', '', ''];
            }

            foreach ([
                'total' => __('messages.report_records_marked'),
                'present' => __('messages.present'),
                'absent' => __('messages.absent'),
                'late' => __('messages.late'),
                'days' => __('messages.report_days_marked'),
                'classes' => __('messages.report_classes_marked'),
            ] as $key => $label) {
                $rows[] = [__('messages.report_attendance'), $label, '', '', $report['attendance'][$key]];
            }
        }

        return $this->csvDownload('teacher-report-'.($teacher?->id ?? 'none').'-'.$period->startDate().'-'.$period->endDate().'.csv', $rows);
    }

    public function classes(ReportRangeRequest $request)
    {
        $data = $this->classContext($request);
        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.classes', array_merge($data, compact('classes')));
    }

    public function classPrint(ReportRangeRequest $request)
    {
        return view('admin.reports.print.class', $this->classContext($request));
    }

    public function classCsv(ReportRangeRequest $request)
    {
        $data = $this->classContext($request);
        $class = $data['class'];
        $period = $data['period'];
        $report = $data['report'];

        $rows = [$this->csvHeader()];
        $rows[] = [__('messages.report_profile'), __('messages.class'), '', '', $class?->name ?? __('messages.report_no_class')];
        $rows[] = [__('messages.report_profile'), __('messages.class_teacher'), '', '', $class?->classTeacher?->user?->name ?? '-'];
        $rows[] = [__('messages.report_profile'), __('messages.student_count'), '', '', $class?->students_count ?? 0];
        $rows[] = [__('messages.report_range'), __('messages.from'), '', '', $period->startDate()];
        $rows[] = [__('messages.report_range'), __('messages.to'), '', '', $period->endDate()];

        if ($report) {
            foreach ([
                'present' => __('messages.present'),
                'absent' => __('messages.absent'),
                'late' => __('messages.late'),
                'school_days' => __('messages.school_days'),
            ] as $key => $label) {
                $rows[] = [__('messages.report_attendance'), $label, '', '', $report['attendance'][$key]];
            }

            $rows[] = [__('messages.report_attendance'), __('messages.percentage_present'), '', '', number_format($report['attendance']['percentage'], 1).'%'];

            foreach ($this->currencies($report['collected'], $report['outstanding']) as $currency) {
                $rows[] = [__('messages.report_fees'), __('messages.report_collected'), '', $currency, $report['collected'][$currency] ?? '0.00'];
                $rows[] = [__('messages.report_fees'), __('messages.report_outstanding'), '', $currency, $report['outstanding'][$currency] ?? '0.00'];
            }

            if ($report['owing']->isEmpty()) {
                $rows[] = [__('messages.report_owing'), __('messages.report_no_owing_students'), '', '', ''];
            } else {
                foreach ($report['owing'] as $row) {
                    foreach ($row['balances'] as $currency => $amount) {
                        $rows[] = [
                            __('messages.report_owing'),
                            __('messages.student_name'),
                            $row['student']->name.' ('.$row['student']->student_id.')',
                            $currency,
                            $amount,
                        ];
                    }
                }
            }
        }

        return $this->csvDownload('class-report-'.($class?->id ?? 'none').'-'.$period->startDate().'-'.$period->endDate().'.csv', $rows);
    }

    public function subjects(ReportRangeRequest $request)
    {
        $data = $this->subjectContext($request);
        $subjects = Subject::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.reports.subjects', array_merge($data, compact('subjects')));
    }

    public function subjectPrint(ReportRangeRequest $request)
    {
        return view('admin.reports.print.subject', $this->subjectContext($request));
    }

    public function subjectCsv(ReportRangeRequest $request)
    {
        $data = $this->subjectContext($request);
        $subject = $data['subject'];
        $period = $data['period'];
        $report = $data['report'];

        $rows = [$this->csvHeader()];
        $rows[] = [__('messages.report_profile'), __('messages.subject_name'), '', '', $subject?->name ?? __('messages.report_no_subject')];
        $rows[] = [__('messages.report_profile'), __('messages.subject_code'), '', '', $subject?->code ?? ''];
        $rows[] = [__('messages.report_range'), __('messages.from'), '', '', $period->startDate()];
        $rows[] = [__('messages.report_range'), __('messages.to'), '', '', $period->endDate()];

        if ($report) {
            $rows[] = [__('messages.report_profile'), __('messages.classes'), '', '', $report['classes']->count()];
            $rows[] = [__('messages.report_profile'), __('messages.teachers'), '', '', $report['teachers']->count()];
            $rows[] = [__('messages.report_profile'), __('messages.student_count'), '', '', $report['students']];

            foreach ($report['classes'] as $row) {
                $rows[] = [__('messages.classes'), __('messages.class'), $row['class']->name, '', $row['attendance']['percentage'].'%'];
                $rows[] = [__('messages.classes'), __('messages.class_teacher'), $row['class']->name, '', $row['class']->classTeacher?->user?->name ?? '-'];
                $rows[] = [__('messages.classes'), __('messages.subject_teacher'), $row['class']->name, '', $row['subject_teacher']?->user?->name ?? '-'];
                $rows[] = [__('messages.classes'), __('messages.student_count'), $row['class']->name, '', $row['class']->students_count];
            }
        }

        return $this->csvDownload('subject-report-'.($subject?->id ?? 'none').'-'.$period->startDate().'-'.$period->endDate().'.csv', $rows);
    }

    private function studentContext(ReportRangeRequest $request): array
    {
        $this->ensureAdmin($request);

        $period = $this->period($request);
        $student = $request->filled('student_id')
            ? Student::with('schoolClass')->findOrFail($request->integer('student_id'))
            : Student::with('schoolClass')->orderBy('student_id')->first();
        $report = $student
            ? (new StudentReportQuery)->forStudent($student, $period, Setting::schoolDays())
            : null;

        return compact('period', 'student', 'report');
    }

    private function teacherContext(ReportRangeRequest $request): array
    {
        $this->ensureAdmin($request);

        $period = $this->period($request);
        $teacher = $request->filled('teacher_id')
            ? Teacher::with('user')->findOrFail($request->integer('teacher_id'))
            : Teacher::with('user')->orderBy('id')->first();
        $report = $teacher
            ? (new TeacherReportQuery)->forTeacher($teacher, $period)
            : null;

        return compact('period', 'teacher', 'report');
    }

    private function classContext(ReportRangeRequest $request): array
    {
        $this->ensureAdmin($request);

        $period = $this->period($request);
        $class = $request->filled('class_id')
            ? SchoolClass::with('classTeacher.user')->findOrFail($request->integer('class_id'))
            : SchoolClass::with('classTeacher.user')->orderBy('name')->first();

        if ($class) {
            $class->loadCount(['students' => fn ($query) => $query->where('status', 'active')]);
        }

        $report = $class
            ? (new ClassReportQuery)->forClass($class, $period, Setting::schoolDays())
            : null;

        return compact('period', 'class', 'report');
    }

    private function schoolContext(ReportRangeRequest $request): array
    {
        $this->ensureAdmin($request);

        $period = $this->period($request);
        $report = (new SchoolReportQuery)->forPeriod($period, Setting::schoolDays());

        return compact('period', 'report');
    }

    private function subjectContext(ReportRangeRequest $request): array
    {
        $this->ensureAdmin($request);

        $period = $this->period($request);
        $subject = $request->filled('subject_id')
            ? Subject::findOrFail($request->integer('subject_id'))
            : Subject::orderBy('name')->first();
        $report = $subject
            ? (new SubjectReportQuery)->forSubject($subject, $period, Setting::schoolDays())
            : null;

        return compact('period', 'subject', 'report');
    }

    private function feeStatusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'fee_unpaid',
            'partial' => 'fee_partial',
            'paid' => 'fee_paid_status',
            default => 'fee_void',
        };
    }

    private function period(ReportRangeRequest $request): ReportPeriod
    {
        return ReportPeriod::make(
            (string) $request->input('range', 'this_month'),
            $request->input('start_date'),
            $request->input('end_date')
        );
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function csvHeader(): array
    {
        return [
            __('messages.report_section'),
            __('messages.report_metric'),
            __('messages.report_reference'),
            __('messages.currency'),
            __('messages.report_value'),
        ];
    }

    private function currencies(array ...$groups): array
    {
        $currencies = [];

        foreach ($groups as $group) {
            $currencies = array_merge($currencies, array_keys($group));
        }

        $currencies = array_values(array_unique($currencies));
        sort($currencies);

        return $currencies;
    }

    private function csvDownload(string $filename, array $rows)
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, array_map([Csv::class, 'safe'], $row), ',', '"', '');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
