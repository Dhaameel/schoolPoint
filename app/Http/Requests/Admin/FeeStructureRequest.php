<?php

namespace App\Http\Requests\Admin;

use App\Support\Currencies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $structure = $this->route('structure');

        return [
            'class_id' => ['required', 'exists:school_classes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('fee_structures', 'name')
                    ->where(fn ($query) => $query
                        ->where('class_id', $this->input('class_id'))
                        ->where('currency', $this->input('currency')))
                    ->ignore($structure?->id),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(Currencies::all())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('messages.fee_structure_duplicate'),
        ];
    }
}
