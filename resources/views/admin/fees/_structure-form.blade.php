@php($editing = isset($structure))
<form method="POST" action="{{ $editing ? route('fees.structures.update', $structure) : route('fees.structures.store') }}">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="class_id">{{ __('messages.class') }}</label>
            <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                <option value="">{{ __('messages.choose') }}</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) old('class_id', $editing ? $structure->class_id : '') === (string) $class->id)>
                        {{ $class->name }}{{ $class->status !== 'active' ? ' ('.__('messages.archived').')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('class_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="name">{{ __('messages.fee_name') }}</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                   value="{{ old('name', $editing ? $structure->name : '') }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="amount">{{ __('messages.fee_amount') }}</label>
            <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                   type="number" min="0.01" step="0.01"
                   value="{{ old('amount', $editing ? $structure->amount : '') }}" required>
            @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="currency">{{ __('messages.fee_currency') }}</label>
            <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                @foreach(\App\Support\Currencies::all() as $currency)
                    <option value="{{ $currency }}" @selected(old('currency', $editing ? $structure->currency : ($defaultCurrency ?? 'USD')) === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
            @error('currency')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       @checked(old('is_active', $editing ? $structure->is_active : true))>
                <label class="form-check-label" for="is_active">{{ __('messages.fee_active') }}</label>
            </div>
            <div class="form-text">{{ __('messages.fee_structure_active_help') }}</div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('fees.structures.index') }}">{{ __('messages.cancel') }}</a>
    </div>
</form>
