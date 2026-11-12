@extends('layouts.app')

@section('title', __('messages.subject_report'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.subject_report') }}</h1>
            <p>{{ $period->display() }}</p>
        </div>
        @if($subject)
            @php($reportQuery = array_merge(['subject_id' => $subject->id], $period->query()))
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('reports.subjects.print', $reportQuery) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</a>
                <a class="btn btn-outline-primary" href="{{ route('reports.subjects.csv', $reportQuery) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
            </div>
        @endif
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('reports.subjects') }}">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="subject_id">{{ __('messages.subject') }}</label>
                    <select class="form-select" id="subject_id" name="subject_id">
                        @foreach($subjects as $option)
                            <option value="{{ $option->id }}" @selected($subject?->id === $option->id)>{{ $option->name }}{{ $option->code ? ' ('.$option->code.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-date-range :period="$period" />
        </form>
    </div>

    @if($subject)
        @include('admin.reports.partials.subject', ['subject' => $subject, 'report' => $report, 'period' => $period])
    @else
        <div class="dashboard-card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-journal-bookmark"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.report_no_subjects') }}</p>
                <a class="btn btn-primary" href="{{ route('subjects.create') }}">{{ __('messages.add_subject') }}</a>
            </div>
        </div>
    @endif
@endsection
