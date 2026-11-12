@extends('layouts.print')

@section('title', __('messages.teacher_report'))

@section('content')
    @php($schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint')))

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $schoolName }}</h1>
            <p class="text-muted mb-0">{{ __('messages.teacher_report') }} &middot; {{ $period->display() }}</p>
            @if($teacher)
                <p class="mb-0">{{ $teacher->user?->name }} &middot; {{ $teacher->user?->username }}</p>
            @endif
        </div>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</button>
    </div>

    @if($teacher)
        @include('admin.reports.partials.teacher', ['teacher' => $teacher, 'report' => $report, 'period' => $period])
    @else
        <p class="text-muted">{{ __('messages.report_no_teachers') }}</p>
    @endif
@endsection
