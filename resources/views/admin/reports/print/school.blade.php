@extends('layouts.print')

@section('title', __('messages.school_report'))

@section('content')
    @php($schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint')))

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $schoolName }}</h1>
            <p class="text-muted mb-0">{{ __('messages.school_report') }} &middot; {{ $period->display() }}</p>
        </div>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</button>
    </div>

    @include('admin.reports.partials.school', ['report' => $report, 'period' => $period])
@endsection
