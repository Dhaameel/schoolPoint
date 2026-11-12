<?php

namespace App\Policies;

use App\Models\User;

class FeePolicy
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

    public function bill(User $user): bool
    {
        return $user->isAdmin();
    }

    public function recordPayment(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reversePayment(User $user): bool
    {
        return $user->isAdmin();
    }

    public function void(User $user): bool
    {
        return $user->isAdmin();
    }
}
