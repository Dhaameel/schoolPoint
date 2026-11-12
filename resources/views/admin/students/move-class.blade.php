@extends('layouts.app')

@section('title', __('messages.move_class'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.move_class') }}</h1>
            <p>{{ $student->name }} · {{ $student->student_id }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.show', $student) }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 620px;">
        <dl class="row mb-4">
            <dt class="col-5">{{ __('messages.current_class') }}</dt>
            <dd class="col-7 text-end">{{ $student->schoolClass?->name ?? '—' }}</dd>
        </dl>

        <form method="POST" action="{{ route('students.move-class.update', $student) }}">
            @csrf
            <label class="form-label" for="class_id">{{ __('messages.new_class') }}</label>
            <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected($student->class_id == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            @error('class_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <button class="btn btn-primary mt-3" type="submit">{{ __('messages.move_class') }}</button>
            <a class="btn btn-outline-secondary mt-3" href="{{ route('students.show', $student) }}">{{ __('messages.cancel') }}</a>
        </form>
    </div>
@endsection
