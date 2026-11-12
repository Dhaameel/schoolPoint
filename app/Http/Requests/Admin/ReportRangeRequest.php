<?php

namespace App\Http\Requests\Admin;

use App\Support\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('range')) {
            $this->merge(['range' => 'this_month']);
        }
    }

    public function rules(): array
    {
        return [
            'range' => ['required', Rule::in(ReportPeriod::RANGES)],
            'start_date' => ['nullable', 'required_if:range,custom', 'date'],
            'end_date' => ['nullable', 'required_if:range,custom', 'date', 'after_or_equal:start_date'],
        ];
    }
}
