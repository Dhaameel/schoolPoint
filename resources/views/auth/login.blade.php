@extends('layouts.guest')

@section('title', __('messages.sign_in').' &middot; '.\App\Models\Setting::get('school_name', config('app.name')))

@section('content')
    <div class="auth-eyebrow">{{ __('messages.welcome_back') }}</div>
    <h1 class="auth-heading">{{ __('messages.login_heading') }}</h1>
    <p class="auth-subheading">{{ __('messages.login_subheading') }}</p>

    @if(session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="username" class="form-label">{{ __('messages.username') }}</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}"
                   class="form-control @error('username') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('username')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('messages.password') }}</label>
            <input id="password" name="password" type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label auth-meta" for="remember">{{ __('messages.remember_me') }}</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('messages.sign_in') }}</button>
    </form>
@endsection
