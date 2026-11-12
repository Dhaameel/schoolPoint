<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use App\Support\Audit;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);

        $search = trim((string) $request->query('search', ''));
        $assignment = $request->query('assignment', 'all');

        $subjects = Subject::query()
            ->withCount('classSubjects')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($assignment === 'assigned', fn ($query) => $query->has('classSubjects'))
            ->when($assignment === 'unassigned', fn ($query) => $query->doesntHave('classSubjects'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.subjects.index', compact('subjects', 'search', 'assignment'));
    }

    public function create()
    {
        $this->authorize('create', Subject::class);

        return view('admin.subjects.create');
    }

    public function store(SubjectRequest $request)
    {
        $this->authorize('create', Subject::class);

        $subject = Subject::create($request->validated());

        Audit::record('subjects.created', 'Subject created.', ['subject_id' => $subject->id, 'name' => $subject->name]);

        return redirect()->route('subjects.index')->with('status', __('messages.subject_created'));
    }

    public function edit(Subject $subject)
    {
        $this->authorize('update', $subject);

        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $this->authorize('update', $subject);

        $subject->update($request->validated());

        Audit::record('subjects.updated', 'Subject updated.', ['subject_id' => $subject->id, 'name' => $subject->name]);

        return redirect()->route('subjects.index')->with('status', __('messages.subject_updated'));
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('delete', $subject);

        if ($subject->classSubjects()->exists()) {
            return back()->with('error', __('messages.subject_delete_blocked'));
        }

        Audit::record('subjects.deleted', 'Subject deleted.', ['subject_id' => $subject->id, 'name' => $subject->name]);
        $subject->delete();

        return redirect()->route('subjects.index')->with('status', __('messages.subject_deleted'));
    }
}
