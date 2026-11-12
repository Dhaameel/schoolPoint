<?php

namespace App\Policies;

use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewPhoto(User $user, \App\Models\Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $teacher = $user->teacher;

        if (! $teacher) {
            return false;
        }

        return $teacher->teachesClass($student->class_id);
    }
}
