@extends('layouts.print')

@section('title', __('messages.attendance_grid'))

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $class?->name }} — {{ $monthStart->format('F Y') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.attendance_grid') }}</p>
        </div>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print_grid') }}</button>
    </div>

    @if($class && $students->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center">
                <thead>
                <tr>
                    <th class="text-start">{{ __('messages.student_name') }}</th>
                    @foreach($days as $day)
                        <th class="{{ \App\Support\SchoolDays::isSchoolDay($day, $schoolDays) ? '' : 'table-secondary text-muted' }}">{{ $day->format('d') }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($students as $student)
                    <tr>
                        <td class="text-start">{{ $student->name }} <span class="text-muted small">{{ $student->student_id }}</span></td>
                        @foreach($days as $day)
                            @php($record = $attendance[$student->id][$day->toDateString()] ?? null)
                            @php($isSchoolDay = \App\Support\SchoolDays::isSchoolDay($day, $schoolDays))
                            <td class="{{ $isSchoolDay ? '' : 'table-secondary' }}">
                                @if($record?->status === 'present') &#10003;
                                @elseif($record?->status === 'absent') &#10005;
                                @elseif($record?->status === 'late') !
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">{{ __('messages.no_attendance_students') }}</p>
    @endif
@endsection
