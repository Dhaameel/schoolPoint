<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillClassFeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fee_structure_id' => [
                'required',
                Rule::exists('fee_structures', 'id')->where('is_active', true),
            ],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
