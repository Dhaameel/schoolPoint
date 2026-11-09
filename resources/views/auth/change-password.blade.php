@extends('layouts.app')

@section('title', __('messages.change_password'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.change_password') }}</h1>
            <p>{{ auth()->user()->name }} &middot; {{ auth()->user()->username }}</p>
        </div>
    </div>

    @if(auth()->user()->must_change_password)
        <div class="alert alert-warning">{{ __('messages.forced_password_note') }}</div>
    @endif

    <div class="dashboard-card" style="max-width: 560px;">
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="current_password">{{ __('messages.current_password') }}</label>
                <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                       name="current_password" type="password" required autocomplete="current-password">
                @error('current_password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">{{ __('messages.new_password') }}</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password"
                       name="password" type="password" required autocomplete="new-password">
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password_confirmation">{{ __('messages.confirm_password') }}</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation"
                       type="password" required autocomplete="new-password">
            </div>

            <button class="btn btn-primary" type="submit">{{ __('messages.change_password') }}</button>
        </form>
    </div>
@endsection
