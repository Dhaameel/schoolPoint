@extends('layouts.app')

@section('title', __('messages.import_students'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.import_students') }}</h1>
            <p>{{ __('messages.import_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 760px;">
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a class="btn btn-outline-primary" href="{{ route('students.import.template') }}"><i class="bi bi-download me-1"></i>{{ __('messages.download_template') }}</a>
        </div>

        <form method="POST" action="{{ route('students.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="file">{{ __('messages.choose_csv') }}</label>
                <input class="form-control @error('file') is-invalid @enderror" id="file" name="file" type="file" accept=".csv,text/csv" required>
                @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <button class="btn btn-primary" type="submit">{{ __('messages.preview_import') }}</button>
        </form>
    </div>
@endsection
