<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Teachers\CreateTeacherAccountAction;
use App\Actions\Teachers\ResetTeacherPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\Teacher;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $teachers = Teacher::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('user', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.teachers.index', compact('teachers', 'search', 'status'));
    }

    public function create()
    {
        $this->authorize('create', Teacher::class);

        return view('admin.teachers.create');
    }

    public function store(TeacherRequest $request, CreateTeacherAccountAction $action)
    {
        $this->authorize('create', Teacher::class);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('teachers', 'public');
        }

        [$teacher, $password] = $action->handle($data);

        return redirect()->route('teachers.slip')->with('teacher_credentials', [
            'name' => $teacher->user->name,
            'username' => $teacher->user->username,
            'password' => $password,
        ]);
    }

    public function slip()
    {
        $this->authorize('viewAny', Teacher::class);

        $credentials = session('teacher_credentials');

        if (! $credentials) {
            return redirect()->route('teachers.index');
        }

        return view('admin.teachers.slip', compact('credentials'));
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);

        $teacher->load(['user', 'classSubjects.subject', 'classSubjects.schoolClass', 'classTeacherClasses']);

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $teacher->load('user');

        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(TeacherRequest $request, Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($teacher->photo_path) {
                Storage::disk('public')->delete($teacher->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
        ]);

        $teacher->update([
            'phone' => $data['phone'] ?? null,
            'photo_path' => $data['photo_path'] ?? $teacher->photo_path,
        ]);

        Audit::record('teachers.updated', 'Teacher updated.', ['teacher_id' => $teacher->id]);

        return redirect()->route('teachers.show', $teacher)->with('status', __('messages.teacher_updated'));
    }

    public function resetPassword(Teacher $teacher, ResetTeacherPasswordAction $action)
    {
        $this->authorize('update', $teacher);

        $password = $action->handle($teacher);

        return redirect()->route('teachers.slip')->with('teacher_credentials', [
            'name' => $teacher->user->name,
            'username' => $teacher->user->username,
            'password' => $password,
        ]);
    }

    public function deactivate(Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $hasAssignments = $teacher->classSubjects()->exists() || $teacher->classTeacherClasses()->exists();

        $teacher->update(['status' => 'inactive']);
        $teacher->user->update(['is_active' => false]);

        Audit::record('teachers.deactivated', 'Teacher deactivated.', ['teacher_id' => $teacher->id]);

        $redirect = back()->with('status', __('messages.teacher_deactivated'));

        if ($hasAssignments) {
            $redirect->with('warning', __('messages.deactivate_warning_assignments'));
        }

        return $redirect;
    }

    public function reactivate(Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $teacher->update(['status' => 'active']);
        $teacher->user->update(['is_active' => true]);

        Audit::record('teachers.reactivated', 'Teacher reactivated.', ['teacher_id' => $teacher->id]);

        return back()->with('status', __('messages.teacher_reactivated'));
    }
}
