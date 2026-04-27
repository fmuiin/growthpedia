<?php

declare(strict_types=1);

namespace App\Modules\Course\Policies;

use App\Models\User;
use App\Modules\Course\Models\Course;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Course $course): bool
    {
        if ($course->status !== 'draft') {
            return false;
        }

        return $user->role === 'admin';
    }
}
