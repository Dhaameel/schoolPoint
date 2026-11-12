@extends('layouts.app')

@section('title', $class->name)

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ $class->name }}</h1>
            <p>{{ __('messages.class_roster') }} · {{ $class->students->count() }} {{ __('messages.student_count') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('classes.edit', $class) }}"><i class="bi bi-pencil me-1"></i>{{ __('messages.edit') }}</a>
            @if($class->status === 'active')
                <form method="POST" action="{{ route('classes.archive', $class) }}" onsubmit="return confirm('{{ __('messages.archive_class') }}?')">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit"><i class="bi bi-archive me-1"></i>{{ __('messages.archive') }}</button>
                </form>
            @else
                <span class="badge bg-secondary align-self-center">{{ __('messages.archived') }}</span>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('classes.index') }}">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.class_teacher') }}</h2></div>
                <form method="POST" action="{{ route('classes.class-teacher.update', $class) }}">
                    @csrf
                    @method('PUT')
                    <select class="form-select" name="class_teacher_id">
                        <option value="">{{ __('messages.unassigned_teacher') }}</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($class->class_teacher_id == $teacher->id)>{{ $teacher->user->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary mt-3" type="submit">{{ __('messages.save') }}</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.class_subjects') }}</h2></div>

                @if($classSubjects->isEmpty())
                    <p class="text-muted">{{ __('messages.no_class_subjects') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle data-table">
                            <thead><tr><th>{{ __('messages.subject') }}</th><th>{{ __('messages.class_teacher') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                            <tbody>
                            @foreach($classSubjects as $classSubject)
                                <tr>
                                    <td data-label="{{ __('messages.subject') }}">{{ $classSubject->subject?->name }}</td>
                                    <td data-label="{{ __('messages.teacher') }}">
                                        <form method="POST" action="{{ route('classes.subjects.update', [$class, $classSubject]) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select class="form-select form-select-sm" name="teacher_id">
                                                <option value="">{{ __('messages.unassigned_teacher') }}</option>
                                                @foreach($teachers as $teacher)
                                                    <option value="{{ $teacher->id }}" @selected($classSubject->teacher_id == $teacher->id)>{{ $teacher->user->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('messages.save') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @php($availableSubjects = $subjects->whereNotIn('id', $classSubjects->pluck('subject_id')))
                @if($availableSubjects->isNotEmpty())
                    <hr>
                    <form method="POST" action="{{ route('classes.subjects.store', $class) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label" for="subject_id">{{ __('messages.add_subject_to_class') }}</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="teacher_id">{{ __('messages.teacher') }} ({{ __('messages.optional') }})</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">{{ __('messages.unassigned_teacher') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">{{ __('messages.add') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="dashboard-card">
                <div class="dashboard-card-head"><h2>{{ __('messages.class_roster') }}</h2></div>

                @if($class->students->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                        <p class="text-muted mb-0">{{ __('messages.no_students_in_class') }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('classes.move.preview', $class) }}">
                        @csrf
                        <div class="alert alert-info py-2">{{ __('messages.move_students_help') }}</div>

                        <div class="table-responsive">
                            <table class="table align-middle data-table">
                                <thead><tr><th><input type="checkbox" id="select-all-students" aria-label="{{ __('messages.select_students') }}"></th><th>{{ __('messages.student_id') }}</th><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.status') }}</th><th>{{ __('messages.phone') }}</th></tr></thead>
                                <tbody>
                                @foreach($class->students as $student)
                                    <tr>
                                        <td data-label="{{ __('messages.select_students') }}"><input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="{{ $student->id }}"></td>
                                        <td data-label="{{ __('messages.student_id') }}">{{ $student->student_id }}</td>
                                        <td data-label="{{ __('messages.student_name') }}">{{ $student->name }}</td>
                                        <td data-label="{{ __('messages.status') }}"><span class="badge {{ $student->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ __('messages.'.$student->status) }}</span></td>
                                        <td data-label="{{ __('messages.phone') }}">{{ $student->guardian_phone }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-2 align-items-end mt-2">
                            <div class="col-md-5">
                                <label class="form-label" for="target_class_id">{{ __('messages.target_class') }}</label>
                                <select class="form-select" id="target_class_id" name="target_class_id" required>
                                    @foreach($otherClasses as $otherClass)
                                        <option value="{{ $otherClass->id }}">{{ $otherClass->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary" type="submit" @disabled($otherClasses->isEmpty())>{{ __('messages.move_students') }}</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('select-all-students')?.addEventListener('change', function () {
        document.querySelectorAll('.student-checkbox').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
    });
</script>
@endpush
