<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Consultancy;
use App\Models\User;

class ConsultancyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function view(User $user, Consultancy $consultancy): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->consultancy_id === $consultancy->id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, Consultancy $consultancy): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function delete(User $user, Consultancy $consultancy): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
