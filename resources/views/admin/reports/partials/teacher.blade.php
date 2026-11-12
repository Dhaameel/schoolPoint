@php($assignmentRows = collect())
@foreach($report['class_teacher_classes'] as $class)
    @php($assignmentRows->push(['class' => $class->name, 'role' => __('messages.class_teacher'), 'subject' => '-']))
@endforeach
@foreach($report['subject_assignments'] as $assignment)
    @php($assignmentRows->push([
        'class' => $assignment->schoolClass?->name ?? '-',
        'role' => __('messages.subject_teacher'),
        'subject' => $assignment->subject?->name ?? '-',
    ]))
@endforeach

<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_profile') }}</h2></div>
            <dl class="row mb-0">
                <dt class="col-5">{{ __('messages.teacher_name') }}</dt>
                <dd class="col-7 text-end">{{ $teacher->user?->name }}</dd>
                <dt class="col-5">{{ __('messages.teacher_username') }}</dt>
                <dd class="col-7 text-end">{{ $teacher->user?->username }}</dd>
                <dt class="col-5">{{ __('messages.status') }}</dt>
                <dd class="col-7 text-end">
                    <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $teacher->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                    </span>
                </dd>
                <dt class="col-5">{{ __('messages.report_assignments') }}</dt>
                <dd class="col-7 text-end">{{ $assignmentRows->count() }}</dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_assignments') }}</h2></div>

            @if($assignmentRows->isEmpty())
                <p class="text-muted mb-0">{{ __('messages.report_no_assignments') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table mb-0">
                        <thead><tr><th>{{ __('messages.class') }}</th><th>{{ __('messages.report_role') }}</th><th>{{ __('messages.subject') }}</th></tr></thead>
                        <tbody>
                        @foreach($assignmentRows as $row)
                            <tr>
                                <td data-label="{{ __('messages.class') }}">{{ $row['class'] }}</td>
                                <td data-label="{{ __('messages.report_role') }}">{{ $row['role'] }}</td>
                                <td data-label="{{ __('messages.subject') }}">{{ $row['subject'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="dashboard-card mt-3">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_attendance_records') }}</h2></div>

            <div class="row g-2">
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.report_records_marked') }}</div><div class="h5 mb-0">{{ $report['attendance']['total'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.present') }}</div><div class="h5 mb-0">{{ $report['attendance']['present'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.absent') }}</div><div class="h5 mb-0">{{ $report['attendance']['absent'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.late') }}</div><div class="h5 mb-0">{{ $report['attendance']['late'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.report_days_marked') }}</div><div class="h5 mb-0">{{ $report['attendance']['days'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.report_classes_marked') }}</div><div class="h5 mb-0">{{ $report['attendance']['classes'] }}</div></div></div>
            </div>
        </div>
    </div>
</div>
