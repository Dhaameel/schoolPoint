@extends('layouts.app')

@section('title', __('messages.class_report'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.class_report') }}</h1>
            <p>{{ $period->display() }}</p>
        </div>
        @if($class)
            @php($reportQuery = array_merge(['class_id' => $class->id], $period->query()))
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('reports.classes.print', $reportQuery) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</a>
                <a class="btn btn-outline-primary" href="{{ route('reports.classes.csv', $reportQuery) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
            </div>
        @endif
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('reports.classes') }}">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                    <select class="form-select" id="class_id" name="class_id">
                        @foreach($classes as $option)
                            <option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-date-range :period="$period" />
        </form>
    </div>

    @if($class)
        @include('admin.reports.partials.class', ['class' => $class, 'report' => $report, 'period' => $period])
    @else
        <div class="dashboard-card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-buildings"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.report_no_classes') }}</p>
                <a class="btn btn-primary" href="{{ route('classes.create') }}">{{ __('messages.add_class') }}</a>
            </div>
        </div>
    @endif
@endsection
