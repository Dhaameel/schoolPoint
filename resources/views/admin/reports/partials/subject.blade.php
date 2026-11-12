@php($classRows = $report['classes'])

<div class="alert alert-light border small mb-3">{{ __('messages.report_subject_attendance_note') }}</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_profile') }}</h2></div>
            <dl class="row mb-0">
                <dt class="col-5">{{ __('messages.subject_name') }}</dt>
                <dd class="col-7 text-end">{{ $subject->name }}</dd>
                <dt class="col-5">{{ __('messages.subject_code') }}</dt>
                <dd class="col-7 text-end">{{ $subject->code ?: '-' }}</dd>
                <dt class="col-5">{{ __('messages.classes') }}</dt>
                <dd class="col-7 text-end">{{ $classRows->count() }}</dd>
                <dt class="col-5">{{ __('messages.teachers') }}</dt>
                <dd class="col-7 text-end">{{ $report['teachers']->count() }}</dd>
                <dt class="col-5">{{ __('messages.student_count') }}</dt>
                <dd class="col-7 text-end">{{ $report['students'] }}</dd>
                @if($report['unassigned_classes'] > 0)
                    <dt class="col-5">{{ __('messages.report_unassigned') }}</dt>
                    <dd class="col-7 text-end"><span class="badge bg-warning text-dark">{{ $report['unassigned_classes'] }}</span></dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_classes_taking_subject') }}</h2></div>

            @if($classRows->isEmpty())
                <p class="text-muted mb-0">{{ __('messages.report_no_classes_for_subject') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table mb-0">
                        <thead><tr><th>{{ __('messages.class') }}</th><th>{{ __('messages.class_teacher') }}</th><th>{{ __('messages.subject_teacher') }}</th><th>{{ __('messages.student_count') }}</th><th>{{ __('messages.percentage_present') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                        <tbody>
                        @foreach($classRows as $row)
                            <tr>
                                <td data-label="{{ __('messages.class') }}"><a href="{{ route('reports.classes', array_merge(['class_id' => $row['class']->id], $period->query())) }}">{{ $row['class']->name }}</a></td>
                                <td data-label="{{ __('messages.class_teacher') }}">{{ $row['class']->classTeacher?->user?->name ?? '-' }}</td>
                                <td data-label="{{ __('messages.subject_teacher') }}">
                                    @if($row['subject_teacher'])
                                        <a href="{{ route('reports.teachers', array_merge(['teacher_id' => $row['subject_teacher']->id], $period->query())) }}">{{ $row['subject_teacher']->user?->name }}</a>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('messages.report_unassigned') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('messages.student_count') }}">{{ $row['class']->students_count }}</td>
                                <td data-label="{{ __('messages.percentage_present') }}">{{ $row['attendance']['percentage'] }}%</td>
                                <td data-label="{{ __('messages.actions') }}" class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('reports.classes', array_merge(['class_id' => $row['class']->id], $period->query())) }}">{{ __('messages.open_report') }}</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
