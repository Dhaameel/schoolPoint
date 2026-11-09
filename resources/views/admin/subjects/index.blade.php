@extends('layouts.app')

@section('title', __('messages.subjects'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.subjects') }}</h1>
            <p>{{ __('messages.subject_list_help') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('subjects.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_subject') }}</a>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('subjects.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_subjects') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="code">{{ __('messages.filter') }}</label>
                <select class="form-select" id="code" name="code">
                    <option value="all" @selected($codeFilter === 'all')>{{ __('messages.all') }}</option>
                    <option value="with" @selected($codeFilter === 'with')>{{ __('messages.with_code') }}</option>
                    <option value="without" @selected($codeFilter === 'without')>{{ __('messages.without_code') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>{{ __('messages.search') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('subjects.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    <div class="dashboard-card">
        @if($subjects->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-journal-bookmark"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_subjects') }}</p>
                <a class="btn btn-primary" href="{{ route('subjects.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_subject') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.subject_name') }}</th>
                        <th>{{ __('messages.subject_code') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($subjects as $subject)
                        <tr>
                            <td data-label="{{ __('messages.subject_name') }}">{{ $subject->name }}</td>
                            <td data-label="{{ __('messages.subject_code') }}">{{ $subject->code ?: '—' }}</td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('subjects.edit', $subject) }}"><i class="bi bi-pencil"></i> {{ __('messages.edit') }}</a>
                                <form class="d-inline" method="POST" action="{{ route('subjects.destroy', $subject) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> {{ __('messages.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $subjects->links() }}</div>
        @endif
    </div>
@endsection
