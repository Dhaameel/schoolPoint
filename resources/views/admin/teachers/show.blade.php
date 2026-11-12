@extends('layouts.app')

@section('title', __('messages.teacher_profile'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ $teacher->user->name }}</h1>
            <p>{{ __('messages.teacher_profile') }} &middot; {{ $teacher->user->username }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('teachers.edit', $teacher) }}"><i class="bi bi-pencil me-1"></i>{{ __('messages.edit') }}</a>
            <a class="btn btn-outline-secondary" href="{{ route('teachers.index') }}">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="text-center mb-3">
                    @if($teacher->photo_path)
                        <img src="{{ route('photos.teachers.show', $teacher) }}" alt="{{ $teacher->user->name }}" width="96" height="96" style="object-fit: cover; border-radius: 50%;">
                    @else
                        <span class="user-avatar" style="width: 96px; height: 96px; font-size: 2rem;"><i class="bi bi-person"></i></span>
                    @endif
                </div>
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('messages.teacher_username') }}</dt>
                    <dd class="col-7 text-end">{{ $teacher->user->username }}</dd>
                    <dt class="col-5">{{ __('messages.phone') }}</dt>
                    <dd class="col-7 text-end">{{ $teacher->phone ?: '-' }}</dd>
                    <dt class="col-5">{{ __('messages.email') }}</dt>
                    <dd class="col-7 text-end">{{ $teacher->user->email ?: '-' }}</dd>
                    <dt class="col-5">{{ __('messages.status') }}</dt>
                    <dd class="col-7 text-end">
                        <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $teacher->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    </dd>
                </dl>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <form method="POST" action="{{ route('teachers.reset-password', $teacher) }}" onsubmit="return confirm('{{ __('messages.reset_password') }}?')">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-key me-1"></i>{{ __('messages.reset_password') }}</button>
                    </form>
                    @if($teacher->status === 'active')
                        <form method="POST" action="{{ route('teachers.deactivate', $teacher) }}" onsubmit="return confirm('{{ __('messages.deactivate') }}?')">
                            @csrf
                            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-person-x me-1"></i>{{ __('messages.deactivate') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('teachers.reactivate', $teacher) }}">
                            @csrf
                            <button class="btn btn-outline-success" type="submit"><i class="bi bi-person-check me-1"></i>{{ __('messages.reactivate') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.classes_and_subjects') }}</h2></div>

                @if($teacher->classTeacherClasses->isEmpty() && $teacher->classSubjects->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-easel"></i></div>
                        <p class="text-muted mb-0">{{ __('messages.no_assignments_yet') }}</p>
                    </div>
                @else
                    @if($teacher->classTeacherClasses->isNotEmpty())
                        <h3 class="h6">{{ __('messages.class_teacher_of') }}</h3>
                        <ul class="list-group mb-3">
                            @foreach($teacher->classTeacherClasses as $class)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('classes.show', $class) }}">{{ $class->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($teacher->classSubjects->isNotEmpty())
                        <h3 class="h6">{{ __('messages.subject_teacher_of') }}</h3>
                        <div class="table-responsive">
                            <table class="table table-sm data-table">
                                <thead><tr><th>{{ __('messages.class_name') }}</th><th>{{ __('messages.subject') }}</th></tr></thead>
                                <tbody>
                                @foreach($teacher->classSubjects as $assignment)
                                    <tr>
                                        <td data-label="{{ __('messages.class_name') }}">{{ $assignment->schoolClass?->name }}</td>
                                        <td data-label="{{ __('messages.subject') }}">{{ $assignment->subject?->name }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
