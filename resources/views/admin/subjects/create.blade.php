@extends('layouts.app')

@section('title', __('messages.add_subject'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.add_subject') }}</h1>
            <p>{{ __('messages.subject_list_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('subjects.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 620px;">
        <form method="POST" action="{{ route('subjects.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="name">{{ __('messages.subject_name') }}</label>
                <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="code">{{ __('messages.subject_code') }}</label>
                <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}">
                @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('subjects.index') }}">{{ __('messages.cancel') }}</a>
        </form>
    </div>
@endsection
