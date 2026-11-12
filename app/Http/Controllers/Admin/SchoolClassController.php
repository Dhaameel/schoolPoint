<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolClassRequest;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $classes = SchoolClass::query()
            ->with('classTeacher.user')
            ->withCount('students')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.classes.index', compact('classes', 'search', 'status'));
    }

    public function create()
    {
        $this->authorize('create', SchoolClass::class);
        $teachers = Teacher::with('user')->where('status', 'active')->get();

        return view('admin.classes.create', compact('teachers'));
    }

    public function store(SchoolClassRequest $request)
    {
        $this->authorize('create', SchoolClass::class);

        $class = SchoolClass::create($request->validated() + ['status' => 'active']);

        Audit::record('classes.created', 'Class created.', ['class_id' => $class->id, 'name' => $class->name]);

        return redirect()->route('classes.show', $class)->with('status', __('messages.class_created'));
    }

    public function show(SchoolClass $class)
    {
        $this->authorize('view', $class);

        $class->load(['classTeacher.user', 'students' => fn ($query) => $query->orderBy('first_name')]);
        $classSubjects = ClassSubject::with(['subject', 'teacher.user'])
            ->where('class_id', $class->id)
            ->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->where('status', 'active')->orderBy('id')->get();
        $otherClasses = SchoolClass::where('id', '!=', $class->id)->orderBy('name')->get();

        return view('admin.classes.show', compact('class', 'classSubjects', 'subjects', 'teachers', 'otherClasses'));
    }

    public function edit(SchoolClass $class)
    {
        $this->authorize('update', $class);
        $teachers = Teacher::with('user')->where('status', 'active')->get();

        return view('admin.classes.edit', compact('class', 'teachers'));
    }

    public function update(SchoolClassRequest $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $class->update($request->validated());

        Audit::record('classes.updated', 'Class updated.', ['class_id' => $class->id, 'name' => $class->name]);

        return redirect()->route('classes.show', $class)->with('status', __('messages.class_updated'));
    }

    public function archive(SchoolClass $class)
    {
        $this->authorize('update', $class);

        if ($class->students()->where('status', 'active')->exists()) {
            return back()->with('error', __('messages.class_archive_blocked'));
        }

        $class->update(['status' => 'archived']);
        Audit::record('classes.archived', 'Class archived.', ['class_id' => $class->id, 'name' => $class->name]);

        return back()->with('status', __('messages.class_archived'));
    }

    public function updateClassTeacher(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $data = $request->validate([
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        $class->update($data);
        Audit::record('classes.class_teacher_updated', 'Class teacher updated.', ['class_id' => $class->id, 'teacher_id' => $data['class_teacher_id'] ?? null]);

        return back()->with('status', __('messages.class_teacher_updated'));
    }

    public function storeSubject(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $data = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')],
        ]);

        if (ClassSubject::where('class_id', $class->id)->where('subject_id', $data['subject_id'])->exists()) {
            return back()->with('error', __('messages.class_subject_exists'));
        }

        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $data['subject_id'],
            'teacher_id' => $data['teacher_id'] ?? null,
        ]);

        Audit::record('classes.subject_assigned', 'Subject assigned to class.', ['class_id' => $class->id, 'class_subject_id' => $classSubject->id]);

        return back()->with('status', __('messages.class_subject_added'));
    }

    public function updateSubject(Request $request, SchoolClass $class, ClassSubject $classSubject)
    {
        $this->authorize('update', $class);
        abort_unless((int) $classSubject->class_id === (int) $class->id, 404);

        $data = $request->validate([
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')],
        ]);

        $classSubject->update(['teacher_id' => $data['teacher_id'] ?? null]);
        Audit::record('classes.subject_teacher_updated', 'Class subject teacher updated.', ['class_id' => $class->id, 'class_subject_id' => $classSubject->id]);

        return back()->with('status', __('messages.class_subject_updated'));
    }

    public function bulkMovePreview(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'target_class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        if ((int) $data['target_class_id'] === (int) $class->id) {
            return back()->with('error', __('messages.class_move_same_class'));
        }

        $students = Student::whereIn('id', $data['student_ids'])->where('class_id', $class->id)->orderBy('first_name')->get();

        if ($students->count() !== count($data['student_ids'])) {
            return back()->with('error', __('messages.class_move_invalid_students'));
        }

        $targetClass = SchoolClass::findOrFail($data['target_class_id']);

        return view('admin.classes.move-preview', compact('class', 'students', 'targetClass'));
    }

    public function bulkMove(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'target_class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        if ((int) $data['target_class_id'] === (int) $class->id) {
            return back()->with('error', __('messages.class_move_same_class'));
        }

        $students = Student::whereIn('id', $data['student_ids'])->where('class_id', $class->id)->get();

        if ($students->count() !== count($data['student_ids'])) {
            return back()->with('error', __('messages.class_move_invalid_students'));
        }

        DB::transaction(function () use ($students, $data, $class) {
            Student::whereIn('id', $students->pluck('id'))->update(['class_id' => $data['target_class_id']]);

            Audit::record('classes.students_moved', 'Students moved to another class.', [
                'source_class_id' => $class->id,
                'target_class_id' => $data['target_class_id'],
                'student_ids' => $students->pluck('id')->all(),
                'count' => $students->count(),
            ]);
        });

        return redirect()->route('classes.show', $class)->with('status', __('messages.class_students_moved'));
    }
}
