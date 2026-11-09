@extends('layouts.app')

@section('title', __('messages.teachers'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.teachers') }}</h1>
            <p>{{ __('messages.teacher_list_help') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('teachers.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_teacher') }}</a>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('teachers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_teachers') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">{{ __('messages.filter') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('messages.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('messages.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>{{ __('messages.search') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('teachers.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    <div class="dashboard-card">
        @if($teachers->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-person-video2"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_teachers') }}</p>
                <a class="btn btn-primary" href="{{ route('teachers.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_teacher') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.teacher_name') }}</th>
                        <th>{{ __('messages.teacher_username') }}</th>
                        <th>{{ __('messages.phone') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($teachers as $teacher)
                        <tr>
                            <td data-label="{{ __('messages.teacher_name') }}">
                                <div class="d-flex align-items-center gap-2">
                                    @if($teacher->photo_path)
                                        <img src="{{ asset('storage/'.$teacher->photo_path) }}" alt="" width="32" height="32" style="object-fit: cover; border-radius: 50%;">
                                    @else
                                        <span class="user-avatar"><i class="bi bi-person"></i></span>
                                    @endif
                                    <a href="{{ route('teachers.show', $teacher) }}">{{ $teacher->user->name }}</a>
                                </div>
                            </td>
                            <td data-label="{{ __('messages.teacher_username') }}">{{ $teacher->user->username }}</td>
                            <td data-label="{{ __('messages.phone') }}">{{ $teacher->phone ?: '—' }}</td>
                            <td data-label="{{ __('messages.status') }}">
                                <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $teacher->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                                </span>
                            </td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('teachers.show', $teacher) }}"><i class="bi bi-eye"></i> {{ __('messages.view') }}</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('teachers.edit', $teacher) }}"><i class="bi bi-pencil"></i> {{ __('messages.edit') }}</a>
                                <form class="d-inline" method="POST" action="{{ route('teachers.reset-password', $teacher) }}" onsubmit="return confirm('{{ __('messages.reset_password') }}?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-key"></i></button>
                                </form>
                                @if($teacher->status === 'active')
                                    <form class="d-inline" method="POST" action="{{ route('teachers.deactivate', $teacher) }}" onsubmit="return confirm('{{ __('messages.deactivate') }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-person-x"></i></button>
                                    </form>
                                @else
                                    <form class="d-inline" method="POST" action="{{ route('teachers.reactivate', $teacher) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-person-check"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $teachers->links() }}</div>
        @endif
    </div>
@endsection
