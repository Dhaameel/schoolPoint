@extends('layouts.app')

@section('title', __('messages.reports'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.school_report') }}</h1>
            <p>{{ __('messages.reports_help') }} &middot; {{ $period->display() }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('reports.school.print', $period->query()) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('reports.school.csv', $period->query()) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
        </div>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('reports.index') }}">
            <x-date-range :period="$period" />
        </form>
    </div>

    @include('admin.reports.partials.school', ['report' => $report, 'period' => $period])

    <h2 class="h4 mt-4 mb-2">{{ __('messages.report_go_deeper') }}</h2>
    <div class="row g-3">
        @foreach([
            ['route' => 'reports.students', 'title' => 'student_report', 'help' => 'student_report_help', 'icon' => 'bi-person-lines-fill'],
            ['route' => 'reports.teachers', 'title' => 'teacher_report', 'help' => 'teacher_report_help', 'icon' => 'bi-person-video2'],
            ['route' => 'reports.classes', 'title' => 'class_report', 'help' => 'class_report_help', 'icon' => 'bi-buildings'],
            ['route' => 'reports.subjects', 'title' => 'subject_report', 'help' => 'subject_report_help', 'icon' => 'bi-journal-bookmark'],
        ] as $link)
            <div class="col-md-6 col-xl-3">
                <div class="dashboard-card h-100">
                    <div class="empty-state-icon mb-3"><i class="bi {{ $link['icon'] }}"></i></div>
                    <h3 class="h6">{{ __('messages.'.$link['title']) }}</h3>
                    <p class="text-muted small">{{ __('messages.'.$link['help']) }}</p>
                    <a class="btn btn-sm btn-primary" href="{{ route($link['route'], $period->query()) }}">{{ __('messages.open_report') }}</a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
