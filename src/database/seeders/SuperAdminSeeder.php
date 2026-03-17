<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@ocsoluciones.es'],
            [
                'name' => 'Super Admin',
                'password' => 'Superadmin2026!',
                'role' => UserRole::SuperAdmin,
                'consultancy_id' => null,
            ]
        );
    }
}
