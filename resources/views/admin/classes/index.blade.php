@extends('layouts.app')

@section('title', __('messages.classes'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.classes') }}</h1>
            <p>{{ __('messages.class_list_help') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_class') }}</a>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('classes.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_classes') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">{{ __('messages.filter') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('messages.active') }}</option>
                    <option value="archived" @selected($status === 'archived')>{{ __('messages.archived') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>{{ __('messages.search') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('classes.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    <div class="dashboard-card">
        @if($classes->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-buildings"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_classes') }}</p>
                <a class="btn btn-primary" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_class') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.class_name') }}</th>
                        <th>{{ __('messages.class_teacher') }}</th>
                        <th>{{ __('messages.student_count') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($classes as $class)
                        <tr>
                            <td data-label="{{ __('messages.class_name') }}"><a href="{{ route('classes.show', $class) }}">{{ $class->name }}</a></td>
                            <td data-label="{{ __('messages.class_teacher') }}">{{ $class->classTeacher?->user?->name ?? '—' }}</td>
                            <td data-label="{{ __('messages.student_count') }}">{{ $class->students_count }}</td>
                            <td data-label="{{ __('messages.status') }}">
                                <span class="badge {{ $class->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $class->status === 'active' ? __('messages.active') : __('messages.archived') }}
                                </span>
                            </td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('classes.show', $class) }}"><i class="bi bi-eye"></i> {{ __('messages.view') }}</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('classes.edit', $class) }}"><i class="bi bi-pencil"></i> {{ __('messages.edit') }}</a>
                                @if($class->status === 'active')
                                    <form class="d-inline" method="POST" action="{{ route('classes.archive', $class) }}" onsubmit="return confirm('{{ __('messages.archive_class') }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-archive"></i> {{ __('messages.archive') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $classes->links() }}</div>
        @endif
    </div>
@endsection
