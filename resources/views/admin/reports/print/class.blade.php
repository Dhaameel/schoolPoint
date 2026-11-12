@extends('layouts.print')

@section('title', __('messages.class_report'))

@section('content')
    @php($schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint')))

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $schoolName }}</h1>
            <p class="text-muted mb-0">{{ __('messages.class_report') }} &middot; {{ $period->display() }}</p>
            @if($class)
                <p class="mb-0">{{ $class->name }}</p>
            @endif
        </div>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</button>
    </div>

    @if($class)
        @include('admin.reports.partials.class', ['class' => $class, 'report' => $report, 'period' => $period])
    @else
        <p class="text-muted">{{ __('messages.report_no_classes') }}</p>
    @endif
@endsection
