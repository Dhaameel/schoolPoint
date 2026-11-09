@extends('layouts.app')

@section('title', __('messages.add_teacher'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.add_teacher') }}</h1>
            <p>{{ __('messages.teacher_list_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('teachers.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 720px;">
        <form method="POST" action="{{ route('teachers.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">{{ __('messages.teacher_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="username">{{ __('messages.teacher_username') }}</label>
                    <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
                    <div class="form-text">{{ __('messages.teacher_username_help') }}</div>
                    @error('username')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">{{ __('messages.phone') }}</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">{{ __('messages.email') }}</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}">
                    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="photo">{{ __('messages.photo') }}</label>
                    <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/*">
                    @error('photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <p class="text-muted small mb-0">{{ __('messages.temporary_password') }}: {{ __('messages.password_slip_help') }}</p>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('teachers.index') }}">{{ __('messages.cancel') }}</a>
                </div>
            </div>
        </form>
    </div>
@endsection
