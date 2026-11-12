<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\SaveAttendanceAction;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Queries\Attendance\ClassAttendanceSummary;
use App\Support\Csv;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        return view('admin.attendance.index', $this->gridData($request));
    }

    public function take(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $classes = SchoolClass::orderBy('name')->get();
        $class = $classes->firstWhere('id', $request->query('class_id')) ?? $classes->first();
        $date = $this->validDate($request->query('date', now()->toDateString()));

        if (! $class) {
            return redirect()->route('attendance.index')->with('error', __('messages.no_classes'));
        }

        $this->authorize('markClass', [Attendance::class, $class]);

        return view('attendance.take', $this->takeData($class, $date, $classes));
    }

    public function store(Request $request, SaveAttendanceAction $action)
    {
        $this->authorize('viewAny', Attendance::class);

        $data = $request->validate([
            'class_id' => ['required', 'exists:school_classes,id'],
            'date' => ['required', 'date'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['in:present,absent,late'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $this->authorize('markClass', [Attendance::class, $class]);

        $result = $action->handle($class, $this->validDate($data['date']), $data['statuses'] ?? [], $data['note'] ?? null, $request->user());

        $redirect = redirect()->route('attendance.take', ['class_id' => $class->id, 'date' => $data['date']])
            ->with('status', __('messages.attendance_saved'));

        if ($result['skipped'] > 0) {
            $redirect->with('warning', __('messages.attendance_already_marked', ['count' => $result['skipped']]));
        }

        return $redirect;
    }

    public function print(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        return view('attendance.print', $this->gridData($request));
    }

    public function csv(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $data = $this->gridData($request);

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            $header = ['Student ID', 'Student name'];
            foreach ($data['days'] as $day) {
                $header[] = $day->format('D d');
            }
            fputcsv($handle, array_map([Csv::class, 'safe'], $header), ',', '"', '');

            foreach ($data['students'] as $student) {
                $row = [Csv::safe($student->student_id), Csv::safe($student->name)];
                foreach ($data['days'] as $day) {
                    $row[] = ($data['attendance'][$student->id][$day->toDateString()] ?? null)?->status ?? '';
                }
                fputcsv($handle, $row, ',', '"', '');
            }

            fclose($handle);
        }, 'attendance-'.($data['class']?->id ?? 'class').'-'.$data['monthStart']->format('Y-m').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function gridData(Request $request): array
    {
        $classes = SchoolClass::orderBy('name')->get();
        $class = $classes->firstWhere('id', $request->query('class_id')) ?? $classes->first();
        $month = (string) $request->query('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $monthStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $schoolDays = Setting::schoolDays();
        $days = collect(CarbonPeriod::create($monthStart, $monthEnd))->map(fn ($day) => $day->copy());
        $students = $class
            ? Student::where('class_id', $class->id)
                ->where(function ($query) use ($monthStart, $monthEnd) {
                    $query->where('status', 'active')
                        ->orWhereHas('attendances', fn ($query) => $query->whereBetween('date', [
                            $monthStart->toDateString(),
                            $monthEnd->toDateString(),
                        ]));
                })
                ->orderBy('first_name')
                ->get()
            : collect();
        $attendance = $class
            ? Attendance::where('class_id', $class->id)
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->get()
                ->groupBy('student_id')
                ->map(fn ($rows) => $rows->keyBy(fn ($row) => $row->date->toDateString()))
            : collect();
        $summary = $class ? (new ClassAttendanceSummary)->forClass($class->id, $monthStart, $monthEnd, $schoolDays) : null;

        $dailyCounts = [];
        $maxDaily = 1;

        foreach ($days as $day) {
            $date = $day->toDateString();
            $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];

            foreach ($students as $student) {
                $record = $attendance->get($student->id)?->get($date);

                if ($record) {
                    $counts[$record->status]++;
                    $counts['total']++;
                }
            }

            $dailyCounts[$date] = $counts;
            $maxDaily = max($maxDaily, $counts['total']);
        }

        return compact('classes', 'class', 'month', 'monthStart', 'days', 'students', 'attendance', 'schoolDays', 'summary', 'dailyCounts', 'maxDaily');
    }

    private function takeData(SchoolClass $class, string $date, $classes): array
    {
        $students = Student::where('class_id', $class->id)->where('status', 'active')->orderBy('first_name')->get();
        $existing = Attendance::with('schoolClass')
            ->whereDate('date', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return [
            'class' => $class,
            'classes' => $classes,
            'date' => $date,
            'students' => $students,
            'existing' => $existing,
            'isTeacher' => false,
            'storeRoute' => route('attendance.store'),
        ];
    }

    private function validDate(?string $date): string
    {
        return $date && strtotime($date) ? Carbon::parse($date)->toDateString() : now()->toDateString();
    }
}
