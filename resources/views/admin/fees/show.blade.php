@extends('layouts.app')

@section('title', $fee->title)

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ $fee->title }}</h1>
            <p>
                <a href="{{ route('students.show', $fee->student) }}">{{ $fee->student?->name }}</a>
                &middot; {{ $fee->schoolClass?->name ?? '-' }}
                &middot; {{ $fee->student?->student_id }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('fees.bill') }}"><i class="bi bi-receipt me-1"></i>{{ __('messages.bill_class') }}</a>
            <a class="btn btn-outline-secondary" href="{{ route('fees.index') }}">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="dashboard-card">
                <div class="dashboard-card-head"><h2>{{ __('messages.fee_details') }}</h2></div>
                @php($statusClasses = ['unpaid' => 'bg-secondary', 'partial' => 'bg-warning text-dark', 'paid' => 'bg-success', 'void' => 'bg-dark'])
                @php($statusLabels = ['unpaid' => 'fee_unpaid', 'partial' => 'fee_partial', 'paid' => 'fee_paid_status', 'void' => 'fee_void'])
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('messages.status') }}</dt>
                    <dd class="col-7 text-end">
                        <span class="badge {{ $statusClasses[$fee->status] ?? 'bg-secondary' }}">{{ __('messages.'.($statusLabels[$fee->status] ?? 'fee_unpaid')) }}</span>
                    </dd>
                    <dt class="col-5">{{ __('messages.fee_student') }}</dt>
                    <dd class="col-7 text-end"><a href="{{ route('students.show', $fee->student) }}">{{ $fee->student?->name }}</a></dd>
                    <dt class="col-5">{{ __('messages.class') }}</dt>
                    <dd class="col-7 text-end">{{ $fee->schoolClass?->name ?? '-' }}</dd>
                    <dt class="col-5">{{ __('messages.fee_structure') }}</dt>
                    <dd class="col-7 text-end">{{ $fee->structure?->name ?? '-' }}</dd>
                    <dt class="col-5">{{ __('messages.fee_amount') }}</dt>
                    <dd class="col-7 text-end">{{ \App\Support\Money::format($fee->amount, $fee->currency) }}</dd>
                    <dt class="col-5">{{ __('messages.fee_paid') }}</dt>
                    <dd class="col-7 text-end">{{ \App\Support\Money::format($netPaid, $fee->currency) }}</dd>
                    <dt class="col-5 fw-semibold">{{ __('messages.fee_balance') }}</dt>
                    <dd class="col-7 text-end fw-semibold">{{ \App\Support\Money::format($balance, $fee->currency) }}</dd>
                    <dt class="col-5">{{ __('messages.fee_due_date') }}</dt>
                    <dd class="col-7 text-end">{{ $fee->due_date?->format('j M Y') ?? '-' }}</dd>
                    <dt class="col-5">{{ __('messages.fee_billed_on') }}</dt>
                    <dd class="col-7 text-end">{{ $fee->billed_at?->format('j M Y') ?? '-' }}</dd>
                </dl>

                @if($fee->status === 'void')
                    <div class="alert alert-secondary mt-3 mb-0">
                        <div class="fw-semibold">{{ __('messages.fee_void') }}</div>
                        <div>{{ __('messages.voided_on') }}: {{ $fee->voided_at?->format('j M Y') }}</div>
                        <div>{{ __('messages.void_reason_label') }}: {{ $fee->void_reason }}</div>
                    </div>
                @endif
            </div>

            @if($fee->status !== 'void')
                <div class="dashboard-card mt-3">
                    <div class="dashboard-card-head"><h2>{{ __('messages.void_fee') }}</h2></div>
                    <p class="text-muted small">{{ __('messages.void_fee_help') }}</p>

                    @if(bccomp($netPaid, '0.00', 2) === 0)
                        <form method="POST" action="{{ route('fees.void', $fee) }}" onsubmit="return confirm('{{ __('messages.void_fee') }}?')">
                            @csrf
                            <label class="form-label" for="void_reason">{{ __('messages.void_reason') }}</label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" id="void_reason" name="reason" rows="2" required></textarea>
                            @error('reason')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            <button class="btn btn-outline-danger mt-3" type="submit"><i class="bi bi-slash-circle me-1"></i>{{ __('messages.void_fee') }}</button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">{{ __('messages.void_blocked_payments') }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            @if($fee->status === 'void')
                <div class="dashboard-card">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-slash-circle"></i></div>
                        <p class="text-muted mb-0">{{ __('messages.fee_void') }}</p>
                    </div>
                </div>
            @else
                <div class="dashboard-card">
                    <div class="dashboard-card-head"><h2>{{ __('messages.record_payment') }}</h2></div>

                    @if(bccomp($balance, '0.00', 2) <= 0)
                        <div class="alert alert-success mb-0">{{ __('messages.no_payment_due') }}</div>
                    @else
                        <form method="POST" action="{{ route('fees.payments.store', $fee) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="amount">{{ __('messages.payment_amount') }}</label>
                                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                                           type="number" min="0.01" step="0.01" max="{{ $balance }}"
                                           value="{{ old('amount', $balance) }}" required>
                                    @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="paid_at">{{ __('messages.payment_date') }}</label>
                                    <input class="form-control @error('paid_at') is-invalid @enderror" id="paid_at" name="paid_at"
                                           type="date" value="{{ old('paid_at', now()->toDateString()) }}" required>
                                    @error('paid_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="reference">{{ __('messages.payment_reference') }}</label>
                                    <input class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference"
                                           value="{{ old('reference') }}" maxlength="100" required>
                                    <div class="form-text">{{ __('messages.payment_reference_help') }}</div>
                                    @error('reference')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="method">{{ __('messages.payment_method') }}</label>
                                    <select class="form-select @error('method') is-invalid @enderror" id="method" name="method">
                                        <option value="">{{ __('messages.not_recorded') }}</option>
                                        <option value="cash" @selected(old('method') === 'cash')>{{ __('messages.cash') }}</option>
                                        <option value="bank" @selected(old('method') === 'bank')>{{ __('messages.bank') }}</option>
                                        <option value="mobile_money" @selected(old('method') === 'mobile_money')>{{ __('messages.mobile_money') }}</option>
                                    </select>
                                    @error('method')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="note">{{ __('messages.payment_note') }}</label>
                                    <input class="form-control @error('note') is-invalid @enderror" id="note" name="note" value="{{ old('note') }}">
                                    @error('note')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-text mt-2">{{ __('messages.payment_form_help') }}</div>
                            <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-receipt me-1"></i>{{ __('messages.record_payment') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-card mt-3">
        <div class="dashboard-card-head"><h2>{{ __('messages.payment_history') }}</h2></div>

        @if($fee->payments->isEmpty())
            <p class="text-muted mb-0">{{ __('messages.no_payments') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.receipt_number') }}</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.paid_on') }}</th>
                        <th>{{ __('messages.reference') }}</th>
                        <th>{{ __('messages.recorded_by') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($fee->payments->sortByDesc('id') as $payment)
                        <tr>
                            <td data-label="{{ __('messages.receipt_number') }}">
                                <a href="{{ route('fees.payments.receipt', $payment) }}"><code>{{ $payment->receipt_number }}</code></a>
                            </td>
                            <td data-label="{{ __('messages.type') }}">
                                <span class="badge {{ $payment->type === 'payment' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $payment->type === 'payment' ? __('messages.payment') : __('messages.reversal') }}
                                </span>
                                @if($payment->reversedBy)
                                    <span class="badge bg-dark ms-1">{{ __('messages.already_reversed') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('messages.amount') }}">
                                {{ $payment->type === 'reversal' ? '-' : '' }}{{ \App\Support\Money::format($payment->amount, $fee->currency) }}
                            </td>
                            <td data-label="{{ __('messages.paid_on') }}">{{ $payment->paid_at?->format('j M Y') }}</td>
                            <td data-label="{{ __('messages.reference') }}"><span class="small">{{ $payment->reference }}</span></td>
                            <td data-label="{{ __('messages.recorded_by') }}">{{ $payment->createdBy?->name ?? '-' }}</td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                @if($payment->type === 'payment' && ! $payment->reversedBy && $fee->status !== 'void')
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reverse-{{ $payment->id }}" aria-expanded="false">
                                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('messages.reverse_payment') }}
                                    </button>
                                    <div class="collapse mt-2" id="reverse-{{ $payment->id }}">
                                        <form method="POST" action="{{ route('fees.payments.reverse', [$fee, $payment]) }}" onsubmit="return confirm('{{ __('messages.reverse_payment') }}?')">
                                            @csrf
                                            <label class="form-label small" for="reason-{{ $payment->id }}">{{ __('messages.reversal_reason') }}</label>
                                            <input class="form-control form-control-sm mb-2" id="reason-{{ $payment->id }}" name="reason" required>
                                            <div class="form-text small mb-2">{{ __('messages.reverse_payment_help') }}</div>
                                            <button class="btn btn-sm btn-danger" type="submit">{{ __('messages.reverse_payment') }}</button>
                                        </form>
                                    </div>
                                @elseif($payment->type === 'reversal')
                                    <span class="text-muted small">{{ $payment->reason }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
