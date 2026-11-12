@extends('layouts.app')

@section('title', __('messages.students'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.students') }}</h1>
            <p>{{ __('messages.student_list_help') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('students.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_student') }}</a>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('students.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_students') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                <select class="form-select" id="class_id" name="class_id">
                    <option value="all" @selected($classId === 'all')>{{ __('messages.all') }}</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">{{ __('messages.student_status') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('messages.active') }}</option>
                    <option value="graduated" @selected($status === 'graduated')>{{ __('messages.graduated') }}</option>
                    <option value="left" @selected($status === 'left')>{{ __('messages.left') }}</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    <div class="dashboard-card">
        @if($students->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_students') }}</p>
                <a class="btn btn-primary" href="{{ route('students.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_student') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.student_id') }}</th>
                        <th>{{ __('messages.student_name') }}</th>
                        <th>{{ __('messages.class') }}</th>
                        <th>{{ __('messages.guardian_phone') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td data-label="{{ __('messages.student_id') }}"><code>{{ $student->student_id }}</code></td>
                            <td data-label="{{ __('messages.student_name') }}"><a href="{{ route('students.show', $student) }}">{{ $student->name }}</a></td>
                            <td data-label="{{ __('messages.class') }}">{{ $student->schoolClass?->name ?? '—' }}</td>
                            <td data-label="{{ __('messages.guardian_phone') }}">{{ $student->guardian_phone }}</td>
                            <td data-label="{{ __('messages.status') }}">
                                <span class="badge {{ $student->status === 'active' ? 'bg-success' : ($student->status === 'graduated' ? 'bg-primary' : 'bg-secondary') }}">
                                    {{ __('messages.'.$student->status) }}
                                </span>
                            </td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('students.show', $student) }}"><i class="bi bi-eye"></i> {{ __('messages.view') }}</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('students.edit', $student) }}"><i class="bi bi-pencil"></i> {{ __('messages.edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
