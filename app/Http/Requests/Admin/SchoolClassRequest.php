<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $class = $this->route('class');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('school_classes', 'name')->ignore($class?->id)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('school_classes', 'code')->ignore($class?->id)],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
        ];
    }
}
