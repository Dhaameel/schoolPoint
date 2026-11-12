<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Photos\StorePhotoAction;
use App\Actions\Students\CreateStudentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        $search = trim((string) $request->query('search', ''));
        $classId = $request->query('class_id', 'all');
        $status = $request->query('status', 'all');

        $students = Student::query()
            ->with('schoolClass')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%")
                        ->orWhere('guardian_phone', 'like', "%{$search}%");
                });
            })
            ->when($classId !== 'all', fn ($query) => $query->where('class_id', $classId))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('student_id')
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classes', 'search', 'classId', 'status'));
    }

    public function create()
    {
        $this->authorize('create', Student::class);
        $classes = SchoolClass::where('status', 'active')->orderBy('name')->get();

        return view('admin.students.create', compact('classes'));
    }

    public function store(StudentRequest $request, CreateStudentAction $action, StorePhotoAction $photoAction)
    {
        $this->authorize('create', Student::class);

        $data = $request->validated();
        $data['status'] = 'active';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $photoAction->handle($request->file('photo'), 'photos/students');
        }

        $student = $action->handle($data);

        return redirect()->route('students.show', $student)->with('status', __('messages.student_created'));
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);
        $student->load('schoolClass');

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorize('update', $student);
        $student->load('schoolClass');
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'classes'));
    }

    public function update(StudentRequest $request, Student $student, StorePhotoAction $photoAction)
    {
        $this->authorize('update', $student);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('local')->delete($student->photo_path);
            }

            $data['photo_path'] = $photoAction->handle($request->file('photo'), 'photos/students');
        }

        $student->update($data);

        Audit::record('students.updated', 'Student updated.', ['student_id' => $student->student_id]);

        return redirect()->route('students.show', $student)->with('status', __('messages.student_updated'));
    }

    public function moveClassForm(Student $student)
    {
        $this->authorize('update', $student);
        $student->load('schoolClass');
        $classes = SchoolClass::where('status', 'active')->orderBy('name')->get();

        return view('admin.students.move-class', compact('student', 'classes'));
    }

    public function moveClass(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $data = $request->validate([
            'class_id' => ['required', 'exists:school_classes,id'],
        ]);

        $oldClassId = $student->class_id;
        $student->update(['class_id' => $data['class_id']]);

        Audit::record('students.class_moved', 'Student moved to another class.', [
            'student_id' => $student->student_id,
            'from_class_id' => $oldClassId,
            'to_class_id' => $data['class_id'],
        ]);

        return redirect()->route('students.show', $student)->with('status', __('messages.student_class_moved'));
    }

    public function changeStatus(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'graduated', 'left'])],
        ]);

        $oldStatus = $student->status;
        $student->update(['status' => $data['status']]);

        Audit::record('students.status_changed', 'Student status changed.', [
            'student_id' => $student->student_id,
            'from' => $oldStatus,
            'to' => $data['status'],
        ]);

        return redirect()->route('students.show', $student)->with('status', __('messages.student_status_changed'));
    }
}
