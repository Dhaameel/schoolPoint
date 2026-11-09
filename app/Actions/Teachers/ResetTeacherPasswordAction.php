<?php

namespace App\Actions\Teachers;

use App\Models\Teacher;
use App\Support\Audit;
use Illuminate\Support\Str;

class ResetTeacherPasswordAction
{
    public function handle(Teacher $teacher): string
    {
        $temporaryPassword = Str::password(12);

        $teacher->user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        Audit::record('teachers.password_reset', 'Teacher password reset.', [
            'teacher_id' => $teacher->id,
            'username' => $teacher->user->username,
        ]);

        return $temporaryPassword;
    }
}
