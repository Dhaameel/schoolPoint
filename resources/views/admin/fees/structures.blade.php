@extends('layouts.app')

@section('title', __('messages.fee_structures'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.fee_structures') }}</h1>
            <p>{{ __('messages.fee_structure_list_help') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('fees.index') }}"><i class="bi bi-cash-stack me-1"></i>{{ __('messages.fee_list') }}</a>
            <a class="btn btn-primary" href="{{ route('fees.structures.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_fee_structure') }}</a>
        </div>
    </div>

    <div class="dashboard-card mb-3">
        <form method="GET" action="{{ route('fees.structures.index') }}" class="row g-2 align-items-end">
            <div class="col-md-7">
                <label class="form-label" for="search">{{ __('messages.search') }}</label>
                <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_fee_structures') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="state">{{ __('messages.status') }}</label>
                <select class="form-select" id="state" name="state">
                    <option value="all" @selected($state === 'all')>{{ __('messages.all') }}</option>
                    <option value="active" @selected($state === 'active')>{{ __('messages.fee_active') }}</option>
                    <option value="inactive" @selected($state === 'inactive')>{{ __('messages.fee_inactive') }}</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('fees.structures.index') }}">{{ __('messages.clear_filters') }}</a>
            </div>
        </form>
    </div>

    <div class="dashboard-card">
        @if($structures->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_fee_structures') }}</p>
                <a class="btn btn-primary" href="{{ route('fees.structures.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_fee_structure') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                    <tr>
                        <th>{{ __('messages.class') }}</th>
                        <th>{{ __('messages.fee_name') }}</th>
                        <th>{{ __('messages.fee_amount') }}</th>
                        <th>{{ __('messages.billed_count') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th class="text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($structures as $structure)
                        <tr>
                            <td data-label="{{ __('messages.class') }}">{{ $structure->schoolClass?->name ?? '-' }}</td>
                            <td data-label="{{ __('messages.fee_name') }}">{{ $structure->name }}</td>
                            <td data-label="{{ __('messages.fee_amount') }}">{{ \App\Support\Money::format($structure->amount, $structure->currency) }}</td>
                            <td data-label="{{ __('messages.billed_count') }}">{{ $structure->fees_count }}</td>
                            <td data-label="{{ __('messages.status') }}">
                                <span class="badge {{ $structure->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $structure->is_active ? __('messages.fee_active') : __('messages.fee_inactive') }}
                                </span>
                            </td>
                            <td data-label="{{ __('messages.actions') }}" class="text-end">
                                @if($structure->is_active)
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('fees.bill', ['structure_id' => $structure->id]) }}"><i class="bi bi-receipt"></i> {{ __('messages.bill_class') }}</a>
                                @endif
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('fees.structures.edit', $structure) }}"><i class="bi bi-pencil"></i> {{ __('messages.edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $structures->links() }}</div>
        @endif
    </div>
@endsection
