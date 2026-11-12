@extends('layouts.app')

@section('title', $class->name)

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ $class->name }}</h1>
            <p>{{ $isClassTeacher ? __('messages.class_teacher_label') : __('messages.subject_teacher') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('teacher.classes') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.subjects_you_teach') }}</h2></div>
                @if($subjects->isEmpty())
                    <p class="text-muted mb-0">{{ __('messages.no_subjects_you_teach') }}</p>
                @else
                    <ul class="list-group">
                        @foreach($subjects as $assignment)
                            <li class="list-group-item">{{ $assignment->subject?->name }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.class_roster') }}</h2></div>
                @if($class->students->isEmpty())
                    <p class="text-muted mb-0">{{ __('messages.no_students_class') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle data-table">
                            <thead><tr><th>{{ __('messages.student_id') }}</th><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.status') }}</th><th>{{ __('messages.guardian_phone') }}</th></tr></thead>
                            <tbody>
                            @foreach($class->students as $student)
                                <tr>
                                    <td data-label="{{ __('messages.student_id') }}">{{ $student->student_id }}</td>
                                    <td data-label="{{ __('messages.student_name') }}">{{ $student->name }}</td>
                                    <td data-label="{{ __('messages.status') }}">{{ __('messages.'.$student->status) }}</td>
                                    <td data-label="{{ __('messages.guardian_phone') }}">{{ $student->guardian_phone }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
