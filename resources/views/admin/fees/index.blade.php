@extends('layouts.app')

@section('title', __('messages.fee_list'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.fee_list') }}</h1>
            <p>{{ __('messages.fee_list_help') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('fees.structures.index') }}"><i class="bi bi-list-check me-1"></i>{{ __('messages.fee_structures') }}</a>
            <a class="btn btn-primary" href="{{ route('fees.bill') }}"><i class="bi bi-receipt me-1"></i>{{ __('messages.bill_class') }}</a>
        </div>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('fees.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_fees') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
                <select class="form-select" id="class_id" name="class_id">
                    <option value="all" @selected($classId === 'all')>{{ __('messages.all') }}</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">{{ __('messages.fee_status') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all') }}</option>
                    <option value="unpaid" @selected($status === 'unpaid')>{{ __('messages.fee_unpaid') }}</option>
                    <option value="partial" @selected($status === 'partial')>{{ __('messages.fee_partial') }}</option>
                    <option value="paid" @selected($status === 'paid')>{{ __('messages.fee_paid_status') }}</option>
                    <option value="void" @selected($status === 'void')>{{ __('messages.fee_void') }}</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('fees.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    @include('admin.fees.partials.analytics', ['analytics' => $analytics])

    <div class="dashboard-card">
        @if($fees->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-cash-stack"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_fees') }}</p>
                <a class="btn btn-primary" href="{{ route('fees.bill') }}"><i class="bi bi-receipt me-1"></i>{{ __('messages.bill_class') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.fee_student') }}</th>
                        <th>{{ __('messages.fee_title') }}</th>
                        <th>{{ __('messages.class') }}</th>
                        <th>{{ __('messages.fee_amount') }}</th>
                        <th>{{ __('messages.fee_paid') }}</th>
                        <th>{{ __('messages.fee_balance') }}</th>
                        <th>{{ __('messages.fee_status') }}</th>
                        <th>{{ __('messages.fee_due_date') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php($statusClasses = ['unpaid' => 'bg-secondary', 'partial' => 'bg-warning text-dark', 'paid' => 'bg-success', 'void' => 'bg-dark'])
                    @php($statusLabels = ['unpaid' => 'fee_unpaid', 'partial' => 'fee_partial', 'paid' => 'fee_paid_status', 'void' => 'fee_void'])
                    @foreach($fees as $fee)
                        @php($balance = \App\Support\FeeBalance::balance($fee))
                        <tr>
                            <td data-label="{{ __('messages.fee_student') }}">
                                <a href="{{ route('students.show', $fee->student) }}">{{ $fee->student?->name ?? '-' }}</a>
                                <div class="small text-muted">{{ $fee->student?->student_id }}</div>
                            </td>
                            <td data-label="{{ __('messages.fee_title') }}">{{ $fee->title }}</td>
                            <td data-label="{{ __('messages.class') }}">{{ $fee->schoolClass?->name ?? '-' }}</td>
                            <td data-label="{{ __('messages.fee_amount') }}">{{ \App\Support\Money::format($fee->amount, $fee->currency) }}</td>
                            <td data-label="{{ __('messages.fee_paid') }}">{{ \App\Support\Money::format($fee->amount_paid, $fee->currency) }}</td>
                            <td data-label="{{ __('messages.fee_balance') }}">{{ \App\Support\Money::format($balance, $fee->currency) }}</td>
                            <td data-label="{{ __('messages.fee_status') }}">
                                <span class="badge {{ $statusClasses[$fee->status] ?? 'bg-secondary' }}">{{ __('messages.'.($statusLabels[$fee->status] ?? 'fee_unpaid')) }}</span>
                            </td>
                            <td data-label="{{ __('messages.fee_due_date') }}">{{ $fee->due_date?->format('j M Y') ?? '-' }}</td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('fees.show', $fee) }}"><i class="bi bi-eye"></i> {{ __('messages.view') }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $fees->links() }}</div>
        @endif
    </div>
@endsection
