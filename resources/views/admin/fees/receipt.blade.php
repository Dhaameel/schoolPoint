@extends('layouts.print')

@section('title', __('messages.receipt_number').' '.$payment->receipt_number)

@section('content')
    @php($schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint')))
    @php($fee = $payment->fee)
    @php($isReversal = $payment->type === 'reversal')

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $schoolName }}</h1>
            <p class="text-muted mb-0">{{ $isReversal ? __('messages.reversal') : __('messages.payment_receipt') }}</p>
        </div>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</button>
    </div>

    <div class="dashboard-card">
        <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom pb-3 mb-3">
            <div>
                <div class="text-muted small">{{ __('messages.receipt_number') }}</div>
                <div class="h5 mb-0"><code>{{ $payment->receipt_number }}</code></div>
            </div>
            <div class="text-end">
                <div class="text-muted small">{{ __('messages.paid_on') }}</div>
                <div class="fw-semibold">{{ $payment->paid_at?->format('j M Y') }}</div>
            </div>
        </div>

        <dl class="row mb-0">
            <dt class="col-5">{{ __('messages.fee_student') }}</dt>
            <dd class="col-7 text-end">{{ $fee->student?->name }} <span class="text-muted small">({{ $fee->student?->student_id }})</span></dd>
            <dt class="col-5">{{ __('messages.class') }}</dt>
            <dd class="col-7 text-end">{{ $fee->schoolClass?->name ?? '-' }}</dd>
            <dt class="col-5">{{ __('messages.fee_title') }}</dt>
            <dd class="col-7 text-end">{{ $fee->title }}</dd>
            <dt class="col-5">{{ __('messages.amount') }}</dt>
            <dd class="col-7 text-end fw-semibold">{{ $isReversal ? '-' : '' }}{{ \App\Support\Money::format($payment->amount, $fee->currency) }}</dd>
            <dt class="col-5">{{ __('messages.payment_method') }}</dt>
            <dd class="col-7 text-end">{{ $payment->method ? __('messages.'.$payment->method) : '-' }}</dd>
            <dt class="col-5">{{ __('messages.reference') }}</dt>
            <dd class="col-7 text-end">{{ $payment->reference }}</dd>
            <dt class="col-5">{{ __('messages.recorded_by') }}</dt>
            <dd class="col-7 text-end">{{ $payment->createdBy?->name ?? '-' }}</dd>
            @if($isReversal)
                <dt class="col-5">{{ __('messages.original_receipt') }}</dt>
                <dd class="col-7 text-end">{{ $payment->reversalOf?->receipt_number ?? '-' }}</dd>
                <dt class="col-5">{{ __('messages.reversal_reason') }}</dt>
                <dd class="col-7 text-end">{{ $payment->reason }}</dd>
            @endif
            @if($payment->note)
                <dt class="col-5">{{ __('messages.note') }}</dt>
                <dd class="col-7 text-end">{{ $payment->note }}</dd>
            @endif
        </dl>
    </div>

    <p class="text-muted small mt-3 mb-0">{{ __('messages.receipt_help') }}</p>
@endsection
