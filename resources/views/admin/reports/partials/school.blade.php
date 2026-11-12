<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="dashboard-card analytics-stat h-100">
            <div class="analytics-label">{{ __('messages.students') }}</div>
            <div class="analytics-value">{{ $report['students_active'] }}</div>
            <div class="analytics-meta">{{ __('messages.of_total', ['total' => $report['students_total']]) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card analytics-stat h-100">
            <div class="analytics-label">{{ __('messages.classes') }}</div>
            <div class="analytics-value">{{ $report['classes_active'] }}</div>
            <div class="analytics-meta">{{ __('messages.of_total', ['total' => $report['classes_total']]) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card analytics-stat h-100">
            <div class="analytics-label">{{ __('messages.teachers') }}</div>
            <div class="analytics-value">{{ $report['teachers_active'] }}</div>
            <div class="analytics-meta">{{ __('messages.of_total', ['total' => $report['teachers_total']]) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card analytics-stat h-100">
            <div class="analytics-label">{{ __('messages.subjects') }}</div>
            <div class="analytics-value">{{ $report['subjects_total'] }}</div>
            <div class="analytics-meta">{{ __('messages.report_across_school') }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-head">
                <h2>{{ __('messages.report_attendance') }}</h2>
                <span class="badge bg-primary">{{ $report['school_days'] }} {{ __('messages.school_days') }}</span>
            </div>

            <div class="analytics-value mb-2">{{ $report['attendance']['percentage'] }}%</div>
            <div class="progress mb-3" style="height: 10px;" role="progressbar" aria-valuenow="{{ $report['attendance']['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: {{ min(100, $report['attendance']['percentage']) }}%"></div>
            </div>

            <div class="row g-2">
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.present') }}</div><div class="h5 mb-0">{{ $report['attendance']['present'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.absent') }}</div><div class="h5 mb-0">{{ $report['attendance']['absent'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.late') }}</div><div class="h5 mb-0">{{ $report['attendance']['late'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.report_possible_marks') }}</div><div class="h5 mb-0">{{ $report['attendance']['possible'] }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_fees') }}</h2></div>

            @php($currencies = array_values(array_unique(array_merge(
                array_keys($report['fees_billed']),
                array_keys($report['fees_collected']),
                array_keys($report['outstanding'])
            ))))
            @php(sort($currencies))

            @if($currencies === [])
                <p class="text-muted mb-0">{{ __('messages.report_no_fees_in_range') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table mb-0">
                        <thead><tr><th>{{ __('messages.currency') }}</th><th>{{ __('messages.report_fees_billed') }}</th><th>{{ __('messages.report_collected') }}</th><th>{{ __('messages.report_outstanding') }}</th></tr></thead>
                        <tbody>
                        @foreach($currencies as $currency)
                            <tr>
                                <td data-label="{{ __('messages.currency') }}">{{ $currency }}</td>
                                <td data-label="{{ __('messages.report_fees_billed') }}">{{ \App\Support\Money::format($report['fees_billed'][$currency] ?? '0.00') }}</td>
                                <td data-label="{{ __('messages.report_collected') }}">{{ \App\Support\Money::format($report['fees_collected'][$currency] ?? '0.00') }}</td>
                                <td data-label="{{ __('messages.report_outstanding') }}" class="fw-semibold">{{ \App\Support\Money::format($report['outstanding'][$currency] ?? '0.00') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach(['unpaid' => 'bg-secondary', 'partial' => 'bg-warning text-dark', 'paid' => 'bg-success', 'void' => 'bg-dark'] as $status => $class)
                    <span class="badge {{ $class }}">{{ __('messages.fee_'.$status) }}: {{ (int) ($report['fee_status_counts'][$status] ?? 0) }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="dashboard-card mt-3">
    <div class="dashboard-card-head">
        <h2>{{ __('messages.report_class_attendance') }}</h2>
    </div>

    @if($report['class_rows']->isEmpty())
        <p class="text-muted mb-0">{{ __('messages.report_no_classes') }}</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle data-table mb-0">
                <thead><tr><th>{{ __('messages.class') }}</th><th>{{ __('messages.class_teacher') }}</th><th>{{ __('messages.student_count') }}</th><th>{{ __('messages.percentage_present') }}</th></tr></thead>
                <tbody>
                @foreach($report['class_rows'] as $row)
                    <tr>
                        <td data-label="{{ __('messages.class') }}"><a href="{{ route('reports.classes', array_merge(['class_id' => $row['class']->id], $period->query())) }}">{{ $row['class']->name }}</a></td>
                        <td data-label="{{ __('messages.class_teacher') }}">{{ $row['class']->classTeacher?->user?->name ?? '-' }}</td>
                        <td data-label="{{ __('messages.student_count') }}">{{ $row['students'] }}</td>
                        <td data-label="{{ __('messages.percentage_present') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px; min-width: 80px;" role="progressbar" aria-valuenow="{{ $row['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, $row['percentage']) }}%"></div>
                                </div>
                                <span>{{ $row['percentage'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
