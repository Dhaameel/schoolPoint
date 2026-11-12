<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reference')) {
            $this->merge(['reference' => trim((string) $this->input('reference'))]);
        }
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:100', Rule::unique('fee_payments', 'reference')],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'paid_at' => ['required', 'date'],
            'method' => ['nullable', Rule::in(['cash', 'bank', 'mobile_money'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.unique' => __('messages.payment_reference_used'),
        ];
    }
}
