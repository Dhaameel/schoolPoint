<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class MyClassController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $classes = SchoolClass::query()
            ->where(function ($query) use ($teacher) {
                $query->where('class_teacher_id', $teacher->id)
                    ->orWhereHas('classSubjects', fn ($query) => $query->where('teacher_id', $teacher->id));
            })
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->withCount('students')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('teacher.classes.index', compact('classes', 'search', 'status', 'teacher'));
    }

    public function show(Request $request, SchoolClass $class)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $isClassTeacher = (int) $class->class_teacher_id === (int) $teacher->id;
        $isSubjectTeacher = $class->classSubjects()->where('teacher_id', $teacher->id)->exists();

        abort_unless($isClassTeacher || $isSubjectTeacher, 403);

        $class->load(['students' => fn ($query) => $query->orderBy('first_name')]);
        $subjects = $class->classSubjects()
            ->with('subject')
            ->where('teacher_id', $teacher->id)
            ->get();

        return view('teacher.classes.show', compact('class', 'teacher', 'isClassTeacher', 'subjects'));
    }
}
