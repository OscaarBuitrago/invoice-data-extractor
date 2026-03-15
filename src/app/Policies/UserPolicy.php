<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], strict: true);
    }

    public function update(User $authUser, User $target): bool
    {
        if ($authUser->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($authUser->role === UserRole::Admin) {
            return $authUser->consultancy_id === $target->consultancy_id;
        }

        return false;
    }

    public function delete(User $authUser, User $target): bool
    {
        return $this->update($authUser, $target);
    }
}
