<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Attendance\SaveAttendanceAction;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $classes = SchoolClass::query()
            ->where(function ($query) use ($teacher) {
                $query->where('class_teacher_id', $teacher->id)
                    ->orWhereHas('classSubjects', fn ($query) => $query->where('teacher_id', $teacher->id));
            })
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $today = now()->toDateString();
        $counts = Attendance::whereDate('date', $today)
            ->whereIn('class_id', $classes->pluck('id'))
            ->selectRaw('class_id, status, count(*) as total')
            ->groupBy('class_id', 'status')
            ->get()
            ->groupBy('class_id');

        return view('teacher.attendance.index', compact('classes', 'counts', 'today'));
    }

    public function take(Request $request, SchoolClass $class)
    {
        $this->authorize('markClass', [Attendance::class, $class]);

        $date = now()->toDateString();

        if ($request->filled('date')) {
            $this->authorize('editDate', [Attendance::class, $class, (string) $request->query('date')]);
        }

        $students = Student::where('class_id', $class->id)->where('status', 'active')->orderBy('first_name')->get();
        $existing = Attendance::with('schoolClass')
            ->whereDate('date', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return view('attendance.take', [
            'class' => $class,
            'classes' => collect(),
            'date' => $date,
            'students' => $students,
            'existing' => $existing,
            'isTeacher' => true,
            'storeRoute' => route('teacher.attendance.store', $class),
        ]);
    }

    public function store(Request $request, SchoolClass $class, SaveAttendanceAction $action)
    {
        $this->authorize('markClass', [Attendance::class, $class]);

        if ($request->filled('date')) {
            $this->authorize('editDate', [Attendance::class, $class, (string) $request->input('date')]);
        }

        $data = $request->validate([
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['in:present,absent,late'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $action->handle($class, now()->toDateString(), $data['statuses'] ?? [], $data['note'] ?? null, $request->user());

        $redirect = redirect()->route('teacher.attendance')->with('status', __('messages.attendance_saved'));

        if ($result['skipped'] > 0) {
            $redirect->with('warning', __('messages.attendance_already_marked', ['count' => $result['skipped']]));
        }

        return $redirect;
    }
}
