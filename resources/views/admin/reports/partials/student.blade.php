@php($currencies = array_values(array_unique(array_merge(
    array_keys($report['fees_billed']),
    array_keys($report['fees_paid']),
    array_keys($report['balance'])
))))
@php(sort($currencies))

<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_profile') }}</h2></div>
            <dl class="row mb-0">
                <dt class="col-5">{{ __('messages.student_name') }}</dt>
                <dd class="col-7 text-end">{{ $student->name }}</dd>
                <dt class="col-5">{{ __('messages.student_id') }}</dt>
                <dd class="col-7 text-end"><code>{{ $student->student_id }}</code></dd>
                <dt class="col-5">{{ __('messages.class') }}</dt>
                <dd class="col-7 text-end">{{ $student->schoolClass?->name ?? '-' }}</dd>
                <dt class="col-5">{{ __('messages.status') }}</dt>
                <dd class="col-7 text-end">
                    <span class="badge {{ $student->status === 'active' ? 'bg-success' : ($student->status === 'graduated' ? 'bg-primary' : 'bg-secondary') }}">
                        {{ __('messages.'.$student->status) }}
                    </span>
                </dd>
                <dt class="col-5">{{ __('messages.guardian_phone') }}</dt>
                <dd class="col-7 text-end">{{ $student->guardian_phone }}</dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_fees') }}</h2></div>

            @if($currencies === [])
                <p class="text-muted mb-0">{{ __('messages.report_no_fees_in_range') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle data-table mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('messages.currency') }}</th>
                            <th>{{ __('messages.report_fees_billed') }}</th>
                            <th>{{ __('messages.report_payments_in_range') }}</th>
                            <th>{{ __('messages.report_balance_now') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($currencies as $currency)
                            <tr>
                                <td data-label="{{ __('messages.currency') }}">{{ $currency }}</td>
                                <td data-label="{{ __('messages.report_fees_billed') }}">{{ \App\Support\Money::format($report['fees_billed'][$currency] ?? '0.00') }}</td>
                                <td data-label="{{ __('messages.report_payments_in_range') }}">{{ \App\Support\Money::format($report['fees_paid'][$currency] ?? '0.00') }}</td>
                                <td data-label="{{ __('messages.report_balance_now') }}" class="fw-semibold">{{ \App\Support\Money::format($report['balance'][$currency] ?? '0.00') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="dashboard-card mt-3">
            <div class="dashboard-card-head"><h2>{{ __('messages.report_attendance') }}</h2></div>

            <div class="row g-2">
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.present') }}</div><div class="h5 mb-0">{{ $report['attendance']['present'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.absent') }}</div><div class="h5 mb-0">{{ $report['attendance']['absent'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.late') }}</div><div class="h5 mb-0">{{ $report['attendance']['late'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.school_days') }}</div><div class="h5 mb-0">{{ $report['attendance']['school_days'] }}</div></div></div>
                <div class="col-6 col-md"><div class="border rounded p-2 text-center"><div class="dashboard-muted">{{ __('messages.percentage_present') }}</div><div class="h5 mb-0">{{ $report['attendance']['percentage'] }}%</div></div></div>
            </div>
        </div>
    </div>
</div>
