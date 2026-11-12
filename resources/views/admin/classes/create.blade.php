@extends('layouts.app')

@section('title', __('messages.add_class'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.add_class') }}</h1>
            <p>{{ __('messages.class_list_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('classes.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 680px;">
        <form method="POST" action="{{ route('classes.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="name">{{ __('messages.class_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="code">{{ __('messages.class_code') }}</label>
                    <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}">
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="class_teacher_id">{{ __('messages.class_teacher') }} ({{ __('messages.optional') }})</label>
                    <select class="form-select" id="class_teacher_id" name="class_teacher_id">
                        <option value="">—</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('class_teacher_id') == $teacher->id)>{{ $teacher->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('classes.index') }}">{{ __('messages.cancel') }}</a>
                </div>
            </div>
        </form>
    </div>
@endsection
