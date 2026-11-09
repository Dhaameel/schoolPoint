@extends('layouts.app')

@section('title', __('messages.security'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.security') }}</h1>
            <p>{{ auth()->user()->name }} &middot; {{ auth()->user()->username }}</p>
        </div>
    </div>

    @if(count($recoveryCodes) > 0)
        <div class="alert alert-warning">
            <strong>{{ __('messages.recovery_codes') }}</strong>
            <p class="mb-2">{{ __('messages.recovery_codes_help') }}</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($recoveryCodes as $code)
                    <code class="recovery-code badge bg-light text-dark border">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.two_factor_authentication') }}</h2></div>
                <p class="text-muted small">
                    {{ $user->hasEnabledTwoFactorAuthentication() ? __('messages.two_factor_status_enabled') : __('messages.two_factor_status_disabled') }}
                </p>

                @if($user->hasEnabledTwoFactorAuthentication())
                    <p class="text-muted small">
                        {{ __('messages.recovery_codes') }}: {{ count($user->two_factor_recovery_codes ?? []) }}
                    </p>
                    <form method="POST" action="{{ route('security.two-factor.disable') }}">
                        @csrf
                        <label class="form-label" for="password">{{ __('messages.current_password') }}</label>
                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required>
                        @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        <button class="btn btn-outline-danger mt-3" type="submit">{{ __('messages.disable_two_factor') }}</button>
                    </form>
                @else
                    <a class="btn btn-primary" href="{{ route('security.two-factor.setup') }}">{{ __('messages.enable_two_factor') }}</a>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.change_password') }}</h2></div>
                <p class="text-muted small">{{ __('messages.login_subheading') }}</p>
                <a class="btn btn-outline-primary" href="{{ route('password.change') }}">{{ __('messages.change_password') }}</a>
            </div>
        </div>
    </div>
@endsection
