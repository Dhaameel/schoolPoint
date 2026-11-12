@php($currencies = array_values(array_unique(array_merge(
    array_keys($report['collected']),
    array_keys($report['outstanding'])
))))
@php(sort($currencies))

<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_profile') }}</h2></div>
            <dl class="row mb-0">
                <dt class="col-5">{{ __('messages.class') }}</dt>
                <dd class="col-7 text-end">{{ $class->name }}</dd>
                <dt class="col-5">{{ __('messages.class_teacher') }}</dt>
                <dd class="col-7 text-end">{{ $class->classTeacher?->user?->name ?? '-' }}</dd>
                <dt class="col-5">{{ __('messages.student_count') }}</dt>
                <dd class="col-7 text-end">{{ $class->students_count }}</dd>
                <dt class="col-5">{{ __('messages.status') }}</dt>
                <dd class="col-7 text-end">
                    <span class="badge {{ $class->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $class->status === 'active' ? __('messages.active') : __('messages.archived') }}
                    </span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_attendance') }}</h2></div>

            <div class="row g-2">
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.present') }}</div><div class="h5 mb-0">{{ $report['attendance']['present'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.absent') }}</div><div class="h5 mb-0">{{ $report['attendance']['absent'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.late') }}</div><div class="h5 mb-0">{{ $report['attendance']['late'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.school_days') }}</div><div class="h5 mb-0">{{ $report['attendance']['school_days'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.percentage_present') }}</div><div class="h5 mb-0">{{ $report['attendance']['percentage'] }}%</div></div></div>
            </div>
        </div>

        <div class="dashboard-card mt-3">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_fees') }}</h2></div>

            @if($currencies === [])
                <p class="text-muted mb-0">{{ __('messages.report_no_fees_in_range') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table mb-0">
                        <thead><tr><th>{{ __('messages.currency') }}</th><th>{{ __('messages.report_collected') }}</th><th>{{ __('messages.report_outstanding') }}</th></tr></thead>
                        <tbody>
                        @foreach($currencies as $currency)
                            <tr>
                                <td data-label="{{ __('messages.currency') }}">{{ $currency }}</td>
                                <td data-label="{{ __('messages.report_collected') }}">{{ \App\Support\Money::format($report['collected'][$currency] ?? '0.00') }}</td>
                                <td data-label="{{ __('messages.report_outstanding') }}" class="fw-semibold">{{ \App\Support\Money::format($report['outstanding'][$currency] ?? '0.00') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="dashboard-card mt-3">
    <div class="dashboard-card-head"><h2>{{ __('messages.report_students_who_owe') }}</h2></div>

    @if($report['owing']->isEmpty())
        <p class="text-muted mb-0">{{ __('messages.report_no_owing_students') }}</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle data-table mb-0">
                <thead><tr><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.student_id') }}</th><th>{{ __('messages.report_outstanding') }}</th></tr></thead>
                <tbody>
                @foreach($report['owing'] as $row)
                    <tr>
                        <td data-label="{{ __('messages.student_name') }}"><a href="{{ route('students.show', $row['student']) }}">{{ $row['student']->name }}</a></td>
                        <td data-label="{{ __('messages.student_id') }}"><code>{{ $row['student']->student_id }}</code></td>
                        <td data-label="{{ __('messages.report_outstanding') }}" class="text-end">
                            @foreach($row['balances'] as $currency => $amount)
                                <div>{{ \App\Support\Money::format($amount, $currency) }}</div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
