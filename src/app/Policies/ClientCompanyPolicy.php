<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ClientCompany;
use App\Models\User;

class ClientCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClientCompany $company): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->consultancy_id === $company->consultancy_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], strict: true);
    }

    public function update(User $user, ClientCompany $company): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::Admin
            && $user->consultancy_id === $company->consultancy_id;
    }
}
