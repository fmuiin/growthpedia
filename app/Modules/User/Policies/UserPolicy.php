<?php

declare(strict_types=1);

namespace App\Modules\User\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins can view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Admins can update any user; users can update themselves.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    /**
     * Only admins can suspend users, and cannot suspend themselves.
     */
    public function suspend(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }

    /**
     * Only admins can assign roles.
     */
    public function assignRole(User $user): bool
    {
        return $user->role === 'admin';
    }
}
