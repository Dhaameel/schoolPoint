@extends('layouts.app')

@section('title', __('messages.reports'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.reports') }}</h1>
            <p>{{ __('messages.reports_help') }}</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="empty-state-icon mb-3"><i class="bi bi-person-lines-fill"></i></div>
                <h2 class="h5">{{ __('messages.student_report') }}</h2>
                <p class="text-muted">{{ __('messages.student_report_help') }}</p>
                <a class="btn btn-primary" href="{{ route('reports.students') }}">{{ __('messages.open_report') }}</a>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="empty-state-icon mb-3"><i class="bi bi-person-video2"></i></div>
                <h2 class="h5">{{ __('messages.teacher_report') }}</h2>
                <p class="text-muted">{{ __('messages.teacher_report_help') }}</p>
                <a class="btn btn-primary" href="{{ route('reports.teachers') }}">{{ __('messages.open_report') }}</a>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="empty-state-icon mb-3"><i class="bi bi-buildings"></i></div>
                <h2 class="h5">{{ __('messages.class_report') }}</h2>
                <p class="text-muted">{{ __('messages.class_report_help') }}</p>
                <a class="btn btn-primary" href="{{ route('reports.classes') }}">{{ __('messages.open_report') }}</a>
            </div>
        </div>
    </div>
@endsection
