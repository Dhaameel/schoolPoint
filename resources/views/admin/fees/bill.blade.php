@extends('layouts.app')

@section('title', __('messages.bill_class'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.bill_class') }}</h1>
            <p>{{ __('messages.bill_class_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('fees.index') }}">{{ __('messages.back') }}</a>
    </div>

    @if($structures->isEmpty())
        <div class="dashboard-card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <h2 class="h5">{{ __('messages.nothing_here') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.no_active_fee_structures') }}</p>
                <a class="btn btn-primary" href="{{ route('fees.structures.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('messages.add_fee_structure') }}</a>
            </div>
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="dashboard-card h-100">
                    <form method="POST" action="{{ route('fees.bill.store') }}" onsubmit="return confirm('{{ __('messages.bill_confirmation') }}')">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="fee_structure_id">{{ __('messages.fee_structure') }}</label>
                            <select class="form-select @error('fee_structure_id') is-invalid @enderror" id="fee_structure_id" name="fee_structure_id" required>
                                @foreach($structures as $structure)
                                    <option value="{{ $structure->id }}"
                                            data-class="{{ $structure->schoolClass?->name }}"
                                            data-amount="{{ \App\Support\Money::format($structure->amount, $structure->currency) }}"
                                            data-active="{{ $structure->active_students_count }}"
                                            data-billed="{{ $structure->billed_count }}"
                                            @selected((string) old('fee_structure_id', $selectedStructureId ?? $structures->first()->id) === (string) $structure->id)>
                                        {{ $structure->schoolClass?->name }} — {{ $structure->name }} ({{ \App\Support\Money::format($structure->amount, $structure->currency) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('fee_structure_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="due_date">{{ __('messages.bill_due_date') }}</label>
                            <input class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}">
                            @error('due_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <button class="btn btn-primary" type="submit"><i class="bi bi-receipt me-1"></i>{{ __('messages.bill_class_action') }}</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-head"><h2>{{ __('messages.bill_preview') }}</h2></div>
                    <dl class="row mb-0">
                        <dt class="col-6">{{ __('messages.class') }}</dt>
                        <dd class="col-6 text-end" id="preview-class">-</dd>
                        <dt class="col-6">{{ __('messages.amount_per_student') }}</dt>
                        <dd class="col-6 text-end" id="preview-amount">-</dd>
                        <dt class="col-6">{{ __('messages.active_students') }}</dt>
                        <dd class="col-6 text-end" id="preview-active">0</dd>
                        <dt class="col-6">{{ __('messages.students_already_billed') }}</dt>
                        <dd class="col-6 text-end" id="preview-billed">0</dd>
                        <dt class="col-6 fw-semibold">{{ __('messages.students_to_bill') }}</dt>
                        <dd class="col-6 text-end fw-semibold" id="preview-to-bill">0</dd>
                    </dl>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    const structureSelect = document.getElementById('fee_structure_id');

    if (structureSelect) {
        const updatePreview = () => {
            const option = structureSelect.selectedOptions[0];

            if (!option) {
                return;
            }

            const active = Number(option.dataset.active || 0);
            const billed = Number(option.dataset.billed || 0);

            document.getElementById('preview-class').textContent = option.dataset.class || '-';
            document.getElementById('preview-amount').textContent = option.dataset.amount || '-';
            document.getElementById('preview-active').textContent = active;
            document.getElementById('preview-billed').textContent = billed;
            document.getElementById('preview-to-bill').textContent = Math.max(0, active - billed);
        };

        structureSelect.addEventListener('change', updatePreview);
        updatePreview();
    }
</script>
@endpush
