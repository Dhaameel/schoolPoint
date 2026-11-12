@extends('layouts.app')

@section('title', __('messages.take_attendance'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.take_attendance') }}</h1>
            <p>{{ $class->name }} · {{ \Carbon\Carbon::parse($date)->format('l, j M Y') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ $isTeacher ? route('teacher.attendance') : route('attendance.index', ['class_id' => $class->id]) }}">{{ __('messages.back') }}</a>
    </div>

    @if(! $isTeacher)
        <div class="dashboard-card mb-3">
            <form method="GET" action="{{ route('attendance.take') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                    <select class="form-select" id="class_id" name="class_id">
                        @foreach($classes as $option)
                            <option value="{{ $option->id }}" @selected($class->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="date">{{ __('messages.date') }}</label>
                    <input class="form-control" id="date" name="date" type="date" value="{{ $date }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit">{{ __('messages.search') }}</button>
                </div>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ $storeRoute }}">
        @csrf
        @if(! $isTeacher)
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <input type="hidden" name="date" value="{{ $date }}">
        @endif

        <div class="dashboard-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">{{ __('messages.class_roster') }}</h2>
                <button class="btn btn-outline-success" type="button" id="mark-all-present"><i class="bi bi-check2-all me-1"></i>{{ __('messages.mark_all_present') }}</button>
            </div>

            @if($students->isEmpty())
                <p class="text-muted mb-0">{{ __('messages.no_attendance_students') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table">
                        <thead><tr><th>{{ __('messages.student_id') }}</th><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.attendance') }}</th></tr></thead>
                        <tbody>
                        @foreach($students as $student)
                            @php($record = $existing->get($student->id))
                            @php($moved = $record && (int) $record->class_id !== (int) $class->id)
                            @php($status = $record && ! $moved ? $record->status : 'present')
                            <tr>
                                <td data-label="{{ __('messages.student_id') }}">{{ $student->student_id }}</td>
                                <td data-label="{{ __('messages.student_name') }}">{{ $student->name }}</td>
                                <td data-label="{{ __('messages.attendance') }}" class="attendance-choice-cell">
                                    @if($moved)
                                        <span class="badge bg-warning text-dark">
                                            {{ __('messages.already_marked_in', ['class' => $record->schoolClass?->name ?? '—']) }}
                                        </span>
                                    @else
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach(['present' => 'success', 'absent' => 'danger', 'late' => 'warning'] as $value => $color)
                                                <input class="btn-check" type="radio" name="statuses[{{ $student->id }}]" id="status-{{ $student->id }}-{{ $value }}" value="{{ $value }}" {{ $status === $value ? 'checked' : '' }}>
                                                <label class="btn btn-outline-{{ $color }} attendance-choice" for="status-{{ $student->id }}-{{ $value }}">
                                                    {{ __('messages.'.$value) }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="form-label" for="note">{{ __('messages.attendance_note') }}</label>
                    <textarea class="form-control" id="note" name="note" rows="2">{{ $existing->first()?->note }}</textarea>
                </div>

                <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('messages.save_attendance') }}</button>
            @endif
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.getElementById('mark-all-present')?.addEventListener('click', function () {
        document.querySelectorAll('input.btn-check[value="present"]').forEach((input) => {
            input.checked = true;
        });
    });
</script>
@endpush
