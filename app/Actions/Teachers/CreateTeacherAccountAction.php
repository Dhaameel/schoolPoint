<?php

namespace App\Actions\Teachers;

use App\Models\Teacher;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTeacherAccountAction
{
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $temporaryPassword = Str::password(12);

            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $temporaryPassword,
                'role' => 'teacher',
                'must_change_password' => true,
                'is_active' => true,
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'status' => 'active',
            ]);

            Audit::record('teachers.created', 'Teacher account created.', [
                'teacher_id' => $teacher->id,
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return [$teacher, $temporaryPassword];
        });
    }
}
