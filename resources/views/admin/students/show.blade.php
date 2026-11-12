@extends('layouts.app')

@section('title', __('messages.student_profile'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ $student->name }}</h1>
            <p>{{ $student->student_id }} &middot; {{ $student->schoolClass?->name ?? '-' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('students.edit', $student) }}"><i class="bi bi-pencil me-1"></i>{{ __('messages.edit') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('students.move-class', $student) }}"><i class="bi bi-arrow-left-right me-1"></i>{{ __('messages.move_class') }}</a>
            <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="text-center mb-3">
                    @if($student->photo_path)
                        <img src="{{ route('photos.students.show', $student) }}" alt="{{ $student->name }}" width="112" height="112" style="object-fit: cover; border-radius: 50%;">
                    @else
                        <span class="user-avatar" style="width: 112px; height: 112px; font-size: 2.3rem;"><i class="bi bi-person"></i></span>
                    @endif
                </div>
                <div class="text-center mb-3">
                    <span class="badge {{ $student->status === 'active' ? 'bg-success' : ($student->status === 'graduated' ? 'bg-primary' : 'bg-secondary') }}">
                        {{ __('messages.'.$student->status) }}
                    </span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('messages.student_id') }}</dt>
                    <dd class="col-7 text-end"><code>{{ $student->student_id }}</code></dd>
                    <dt class="col-5">{{ __('messages.class') }}</dt>
                    <dd class="col-7 text-end">{{ $student->schoolClass?->name ?? '-' }}</dd>
                    <dt class="col-5">{{ __('messages.admission_number') }}</dt>
                    <dd class="col-7 text-end">{{ $student->admission_number ?: '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dashboard-card mb-3">
                <div class="dashboard-card-head"><h2>{{ __('messages.guardian_details') }}</h2></div>
                <dl class="row mb-0">
                    <dt class="col-4">{{ __('messages.guardian_name') }}</dt>
                    <dd class="col-8 text-end">{{ $student->guardian_name }}</dd>
                    <dt class="col-4">{{ __('messages.guardian_phone') }}</dt>
                    <dd class="col-8 text-end">{{ $student->guardian_phone }}</dd>
                    <dt class="col-4">{{ __('messages.student_gender') }}</dt>
                    <dd class="col-8 text-end">{{ $student->gender ?: '-' }}</dd>
                    <dt class="col-4">{{ __('messages.date_of_birth') }}</dt>
                    <dd class="col-8 text-end">{{ $student->date_of_birth?->format('j M Y') ?? '-' }}</dd>
                </dl>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-head"><h2>{{ __('messages.change_status') }}</h2></div>
                <form method="POST" action="{{ route('students.status', $student) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label" for="status">{{ __('messages.student_status') }}</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" @selected($student->status === 'active')>{{ __('messages.active') }}</option>
                            <option value="graduated" @selected($student->status === 'graduated')>{{ __('messages.graduated') }}</option>
                            <option value="left" @selected($student->status === 'left')>{{ __('messages.left') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" type="submit">{{ __('messages.change_status') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
