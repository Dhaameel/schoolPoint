@php($currencies = array_values(array_unique(array_merge(
    array_keys($analytics['billed']),
    array_keys($analytics['collected']),
    array_keys($analytics['outstanding'])
))))
@php(sort($currencies))

<div class="dashboard-card mb-3">
    <div class="dashboard-card-head">
        <h2>{{ __('messages.fee_snapshot') }}</h2>
        <span class="small text-muted">{{ __('messages.fee_snapshot_help') }}</span>
    </div>

    @if($currencies === [])
        <p class="text-muted mb-0">{{ __('messages.no_fees') }}</p>
    @else
        <div class="row g-3">
            @foreach($currencies as $currency)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="analytics-label">{{ $currency }}</span>
                            <span class="analytics-meta">{{ $analytics['rates'][$currency] ?? 0 }}% {{ __('messages.fee_collection_rate') }}</span>
                        </div>
                        <div class="analytics-value mb-2">{{ \App\Support\Money::format($analytics['collected'][$currency] ?? '0.00') }}</div>
                        <div class="progress mb-2" style="height: 7px;" role="progressbar" aria-valuenow="{{ $analytics['rates'][$currency] ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: {{ min(100, $analytics['rates'][$currency] ?? 0) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>{{ __('messages.report_fees_billed') }}: {{ \App\Support\Money::format($analytics['billed'][$currency] ?? '0.00') }}</span>
                            <span>{{ __('messages.report_outstanding') }}: {{ \App\Support\Money::format($analytics['outstanding'][$currency] ?? '0.00') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mt-3">
        @foreach(['unpaid' => 'bg-secondary', 'partial' => 'bg-warning text-dark', 'paid' => 'bg-success', 'void' => 'bg-dark'] as $status => $class)
            <span class="badge {{ $class }}">{{ __('messages.fee_'.$status) }}: {{ (int) ($analytics['status_counts'][$status] ?? 0) }}</span>
        @endforeach
        <span class="badge bg-light text-dark border">{{ __('messages.total_fees') }}: {{ $analytics['total_fees'] }}</span>
    </div>
</div>
