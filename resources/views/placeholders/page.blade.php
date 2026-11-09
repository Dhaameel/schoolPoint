@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1><i class="bi {{ $icon }} me-2"></i>{{ $title }}</h1>
            <p>{{ __('messages.stage_ready', ['stage' => $stage]) }}</p>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi {{ $icon }}"></i></div>
            <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
            <p class="text-muted mb-3">{{ __('messages.stage_ready', ['stage' => $stage]) }}</p>
            <a class="btn btn-primary" href="{{ route('dashboard') }}">
                <i class="bi bi-arrow-left me-1"></i>{{ __('messages.back_to_dashboard') }}
            </a>
        </div>
    </div>
@endsection
