@extends('layouts.guest')

@section('title', __('messages.two_factor_authentication'))

@section('content')
    <div class="auth-eyebrow">{{ __('messages.security') }}</div>
    <h1 class="auth-heading">{{ __('messages.two_factor_authentication') }}</h1>
    <p class="auth-subheading">{{ __('messages.two_factor_subheading') }}</p>

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div class="mb-3">
            <label for="code" class="form-label">{{ __('messages.authentication_code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                   class="form-control @error('code') is-invalid @enderror"
                   required autofocus placeholder="123456">
            @error('code')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('messages.verify_and_sign_in') }}</button>
    </form>

    <div class="auth-divider">{{ __('messages.cancel') }}</div>
    <a class="btn btn-outline-primary w-100" href="{{ route('login') }}">{{ __('messages.cancel') }}</a>
@endsection
