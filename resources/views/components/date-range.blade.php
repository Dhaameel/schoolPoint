@props(['period'])

<div class="row g-2 align-items-end">
    <div class="col-md-3">
        <label class="form-label" for="report_range">{{ __('messages.range') }}</label>
        <select class="form-select @error('range') is-invalid @enderror" id="report_range" name="range">
            @foreach([
                'today' => 'range_today',
                'this_week' => 'range_this_week',
                'this_month' => 'range_this_month',
                'this_year' => 'range_this_year',
                'custom' => 'range_custom',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(old('range', $period->range) === $value)>{{ __('messages.'.$label) }}</option>
            @endforeach
        </select>
        @error('range')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="report_start_date">{{ __('messages.from') }}</label>
        <input class="form-control @error('start_date') is-invalid @enderror" id="report_start_date" name="start_date"
               type="date" value="{{ old('start_date', $period->startDate()) }}" @disabled($period->range !== 'custom')>
        @error('start_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="report_end_date">{{ __('messages.to') }}</label>
        <input class="form-control @error('end_date') is-invalid @enderror" id="report_end_date" name="end_date"
               type="date" value="{{ old('end_date', $period->endDate()) }}" @disabled($period->range !== 'custom')>
        @error('end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary" type="submit">{{ __('messages.apply') }}</button>
    </div>
</div>

@push('scripts')
<script>
    const reportRange = document.getElementById('report_range');
    const reportStart = document.getElementById('report_start_date');
    const reportEnd = document.getElementById('report_end_date');

    if (reportRange && reportStart && reportEnd) {
        const syncReportRange = () => {
            const custom = reportRange.value === 'custom';
            reportStart.disabled = !custom;
            reportEnd.disabled = !custom;
        };

        reportRange.addEventListener('change', syncReportRange);
        syncReportRange();
    }
</script>
@endpush
