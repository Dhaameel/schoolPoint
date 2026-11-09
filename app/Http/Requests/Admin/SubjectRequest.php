<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($subject?->id)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject?->id)],
        ];
    }
}
