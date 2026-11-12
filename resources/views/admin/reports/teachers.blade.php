@extends('layouts.app')

@section('title', __('messages.teacher_report'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.teacher_report') }}</h1>
            <p>{{ $period->display() }}</p>
        </div>
        @if($teacher)
            @php($reportQuery = array_merge(['teacher_id' => $teacher->id], $period->query()))
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('reports.teachers.print', $reportQuery) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</a>
                <a class="btn btn-outline-primary" href="{{ route('reports.teachers.csv', $reportQuery) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
            </div>
        @endif
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('reports.teachers') }}">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="teacher_id">{{ __('messages.teacher') }}</label>
                    <select class="form-select" id="teacher_id" name="teacher_id">
                        @foreach($teachers as $option)
                            <option value="{{ $option->id }}" @selected($teacher?->id === $option->id)>
                                {{ $option->user?->name }} ({{ $option->user?->username }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-date-range :period="$period" />
        </form>
    </div>

    @if($teacher)
        @include('admin.reports.partials.teacher', ['teacher' => $teacher, 'report' => $report, 'period' => $period])
    @else
        <div class="dashboard-card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-person-video2"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.report_no_teachers') }}</p>
                <a class="btn btn-primary" href="{{ route('teachers.create') }}">{{ __('messages.add_teacher') }}</a>
            </div>
        </div>
    @endif
@endsection
