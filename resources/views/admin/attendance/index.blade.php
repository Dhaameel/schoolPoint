@extends('layouts.app')

@section('title', __('messages.attendance'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.attendance') }}</h1>
            <p>{{ __('messages.attendance_help') }}</p>
        </div>
        @if($class)
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('attendance.print', ['class_id' => $class->id, 'month' => $month]) }}"><i class="bi bi-printer me-1"></i>{{ __('messages.print_grid') }}</a>
                <a class="btn btn-outline-primary" href="{{ route('attendance.csv', ['class_id' => $class->id, 'month' => $month]) }}"><i class="bi bi-filetype-csv me-1"></i>{{ __('messages.download_csv') }}</a>
                <a class="btn btn-primary" href="{{ route('attendance.take', ['class_id' => $class->id, 'date' => now()->toDateString()]) }}"><i class="bi bi-check2-square me-1"></i>{{ __('messages.take_attendance') }}</a>
            </div>
        @endif
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('attendance.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                <select class="form-select" id="class_id" name="class_id" onchange="this.form.submit()">
                    @foreach($classes as $option)
                        <option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="month">{{ __('messages.month') }}</label>
                <input class="form-control" id="month" name="month" type="month" value="{{ $month }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">{{ __('messages.search') }}</button>
            </div>
        </form>
    </div>

    @if($class && $summary)
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3"><div class="dashboard-card text-center"><div class="dashboard-muted">{{ __('messages.present') }}</div><div class="h4 mb-0">{{ $summary['present'] }}</div></div></div>
            <div class="col-6 col-md-3"><div class="dashboard-card text-center"><div class="dashboard-muted">{{ __('messages.absent') }}</div><div class="h4 mb-0">{{ $summary['absent'] }}</div></div></div>
            <div class="col-6 col-md-3"><div class="dashboard-card text-center"><div class="dashboard-muted">{{ __('messages.late') }}</div><div class="h4 mb-0">{{ $summary['late'] }}</div></div></div>
            <div class="col-6 col-md-3"><div class="dashboard-card text-center"><div class="dashboard-muted">{{ __('messages.percentage_present') }}</div><div class="h4 mb-0">{{ $summary['percentage'] }}%</div></div></div>
        </div>
    @endif

    <div class="dashboard-card">
        @if(!$class || $students->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-calendar2-check"></i></div>
                <p class="text-muted mb-0">{{ $classes->isEmpty() ? __('messages.no_classes') : __('messages.no_attendance_students') }}</p>
            </div>
        @else
            <div class="table-responsive attendance-grid-scroll">
                <table class="table table-bordered attendance-grid mb-0">
                    <thead>
                    <tr>
                        <th class="sticky-name">{{ __('messages.student_name') }}</th>
                        @foreach($days as $day)
                            <th class="text-center {{ \App\Support\SchoolDays::isSchoolDay($day, $schoolDays) ? '' : 'non-school' }}">
                                <div>{{ $day->format('D') }}</div>
                                <div class="small">{{ $day->format('d') }}</div>
                            </th>
                        @endforeach
                        <th class="text-center">{{ __('messages.percentage_present') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        @php($studentSummary = (new \App\Queries\Attendance\StudentAttendanceSummary())->forStudent($student, $monthStart, $monthStart->copy()->endOfMonth(), $schoolDays))
                        <tr>
                            <td class="sticky-name">
                                <a href="{{ route('students.show', $student) }}">{{ $student->name }}</a>
                                <div class="small text-muted">{{ $student->student_id }}</div>
                            </td>
                            @foreach($days as $day)
                                @php($record = $attendance[$student->id][$day->toDateString()] ?? null)
                                @php($isSchoolDay = \App\Support\SchoolDays::isSchoolDay($day, $schoolDays))
                                <td class="text-center attendance-cell {{ $isSchoolDay ? '' : 'non-school' }}">
                                    @if($isSchoolDay)
                                        <a href="{{ route('attendance.take', ['class_id' => $class->id, 'date' => $day->toDateString()]) }}" class="d-inline-grid" style="min-width: 28px; min-height: 28px; place-items: center;">
                                            @if($record?->status === 'present')
                                                <i class="bi bi-check-lg text-success"></i>
                                            @elseif($record?->status === 'absent')
                                                <i class="bi bi-x-lg text-danger"></i>
                                            @elseif($record?->status === 'late')
                                                <i class="bi bi-clock-fill text-warning"></i>
                                            @endif
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">{{ $studentSummary['percentage'] }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
