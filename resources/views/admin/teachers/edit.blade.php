@extends('layouts.app')

@section('title', __('messages.edit_teacher'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.edit_teacher') }}</h1>
            <p>{{ $teacher->user->name }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('teachers.show', $teacher) }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 720px;">
        <form method="POST" action="{{ route('teachers.update', $teacher) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">{{ __('messages.teacher_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $teacher->user->name) }}" required autofocus>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="username">{{ __('messages.teacher_username') }}</label>
                    <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $teacher->user->username) }}" required>
                    @error('username')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">{{ __('messages.phone') }}</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $teacher->phone) }}">
                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">{{ __('messages.email') }}</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $teacher->user->email) }}">
                    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="photo">{{ __('messages.photo') }}</label>
                    @if($teacher->photo_path)
                        <div class="mb-2"><img src="{{ route('photos.teachers.show', $teacher) }}" alt="" width="56" height="56" style="object-fit: cover; border-radius: 50%;"></div>
                    @endif
                    <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/*">
                    @error('photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('teachers.show', $teacher) }}">{{ __('messages.cancel') }}</a>
                </div>
            </div>
        </form>
    </div>
@endsection
