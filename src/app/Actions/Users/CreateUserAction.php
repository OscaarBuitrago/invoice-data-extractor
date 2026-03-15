<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function handle(array $data): User
    {
        return DB::transaction(fn (): User => User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'consultancy_id' => $this->resolveConsultancyId($data),
        ]));
    }

    private function resolveConsultancyId(array $data): ?string
    {
        $authUser = auth()->user();

        if ($authUser->role === UserRole::SuperAdmin) {
            return $data['consultancy_id'] ?? null;
        }

        return $authUser->consultancy_id;
    }
}
