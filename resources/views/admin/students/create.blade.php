@extends('layouts.app')

@section('title', __('messages.add_student'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.add_student') }}</h1>
            <p>{{ __('messages.student_id_auto') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 820px;">
        <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="first_name">{{ __('messages.student_first_name') }}</label>
                    <input class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus>
                    @error('first_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="last_name">{{ __('messages.student_last_name') }}</label>
                    <input class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="gender">{{ __('messages.student_gender') }}</label>
                    <input class="form-control" id="gender" name="gender" value="{{ old('gender') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="date_of_birth">{{ __('messages.date_of_birth') }}</label>
                    <input class="form-control" id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                    <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('class_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="guardian_name">{{ __('messages.guardian_name') }}</label>
                    <input class="form-control @error('guardian_name') is-invalid @enderror" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}" required>
                    @error('guardian_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="guardian_phone">{{ __('messages.guardian_phone') }}</label>
                    <input class="form-control @error('guardian_phone') is-invalid @enderror" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone') }}" required>
                    @error('guardian_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="admission_number">{{ __('messages.admission_number') }}</label>
                    <input class="form-control @error('admission_number') is-invalid @enderror" id="admission_number" name="admission_number" value="{{ old('admission_number') }}">
                    @error('admission_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="photo">{{ __('messages.photo') }}</label>
                    <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/*">
                    @error('photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">{{ __('messages.cancel') }}</a>
                </div>
            </div>
        </form>
    </div>
@endsection
