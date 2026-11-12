@extends('layouts.app')

@section('title', __('messages.student_report'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.student_report') }}</h1>
            <p>{{ $period->display() }}</p>
        </div>
        @if($student)
            @php($reportQuery = array_merge(['student_id' => $student->id], $period->query()))
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('reports.students.print', $reportQuery) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</a>
                <a class="btn btn-outline-primary" href="{{ route('reports.students.csv', $reportQuery) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
            </div>
        @endif
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('reports.students') }}">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="student_id">{{ __('messages.student') }}</label>
                    <select class="form-select" id="student_id" name="student_id">
                        @foreach($students as $option)
                            <option value="{{ $option->id }}" @selected($student?->id === $option->id)>
                                {{ $option->name }} ({{ $option->student_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-date-range :period="$period" />
        </form>
    </div>

    @if($student)
        @include('admin.reports.partials.student', ['student' => $student, 'report' => $report, 'period' => $period])
    @else
        <div class="dashboard-card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-person-lines-fill"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.report_no_students') }}</p>
                <a class="btn btn-primary" href="{{ route('students.create') }}">{{ __('messages.add_student') }}</a>
            </div>
        </div>
    @endif
@endsection
