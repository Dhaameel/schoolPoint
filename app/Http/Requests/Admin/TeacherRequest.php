<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $userId = $teacher?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
