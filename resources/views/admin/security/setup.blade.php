@extends('layouts.app')

@section('title', __('messages.enable_two_factor'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.enable_two_factor') }}</h1>
            <p>{{ __('messages.two_factor_authentication') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('security.index') }}">{{ __('messages.cancel') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 620px;">
        <p class="text-muted">{{ __('messages.scan_qr') }}</p>
        <div class="text-center mb-3">
            <img src="{{ $qrCode }}" alt="QR code" width="220" height="220" style="border: 1px solid #e9e2cf; border-radius: 12px; padding: 8px; background: #fff;">
        </div>

        <p class="mb-1">{{ __('messages.manual_secret') }}</p>
        <code class="d-block mb-4 p-2 bg-light border rounded">{{ $secret }}</code>

        <form method="POST" action="{{ route('security.two-factor.enable') }}">
            @csrf
            <label class="form-label" for="code">{{ __('messages.confirm_code') }}</label>
            <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <button class="btn btn-primary mt-3" type="submit">{{ __('messages.confirm') }}</button>
        </form>
    </div>
@endsection
