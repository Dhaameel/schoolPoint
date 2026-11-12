<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin()
            || ($user->teacher && $user->teacher->teachesClass($attendance->class_id));
    }

    public function markClass(User $user, SchoolClass $class): bool
    {
        return $user->isAdmin()
            || ($user->teacher && $user->teacher->teachesClass($class->id));
    }

    public function editDate(User $user, SchoolClass $class, string $date): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->teacher
            && $user->teacher->teachesClass($class->id)
            && $date === now()->toDateString();
    }
}
