@extends('layouts.app')

@section('title', __('messages.attendance'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.attendance') }}</h1>
            <p>{{ __('messages.classes_i_teach') }} · {{ \Carbon\Carbon::parse($today)->format('l, j M Y') }}</p>
        </div>
    </div>

    <div class="dashboard-card">
        @if($classes->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-calendar2-check"></i></div>
                <p class="text-muted mb-0">{{ __('messages.no_teacher_classes') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead><tr><th>{{ __('messages.class_name') }}</th><th>{{ __('messages.student_count') }}</th><th>{{ __('messages.attendance_today') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                    <tbody>
                    @foreach($classes as $class)
                        @php($classCounts = $counts[$class->id] ?? collect())
                        <tr>
                            <td data-label="{{ __('messages.class_name') }}">{{ $class->name }}</td>
                            <td data-label="{{ __('messages.student_count') }}">{{ $class->students_count }}</td>
                            <td data-label="{{ __('messages.attendance_today') }}">
                                <span class="badge bg-success">{{ __('messages.present') }}: {{ $classCounts->firstWhere('status', 'present')?->total ?? 0 }}</span>
                                <span class="badge bg-danger">{{ __('messages.absent') }}: {{ $classCounts->firstWhere('status', 'absent')?->total ?? 0 }}</span>
                                <span class="badge bg-warning text-dark">{{ __('messages.late') }}: {{ $classCounts->firstWhere('status', 'late')?->total ?? 0 }}</span>
                            </td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-primary" href="{{ route('teacher.attendance.take', $class) }}"><i class="bi bi-check2-square me-1"></i>{{ __('messages.take_today_attendance') }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
