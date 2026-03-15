<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin (sin asesoría)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@demo.com',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'consultancy_id' => null,
        ]);

        // ── Asesoría A ──────────────────────────────────────────────
        $consultancyA = Consultancy::create([
            'name' => 'Asesoría García & Asociados',
            'tax_id' => 'B11111111',
            'active' => true,
        ]);

        User::create([
            'name' => 'Admin García',
            'email' => 'admin@garcia.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'consultancy_id' => $consultancyA->id,
        ]);

        User::create([
            'name' => 'Consultor García',
            'email' => 'consultor@garcia.com',
            'password' => 'password',
            'role' => UserRole::Consultant,
            'consultancy_id' => $consultancyA->id,
        ]);

        ClientCompany::create([
            'name' => 'Empresa Alpha S.L.',
            'tax_id' => 'B22222222',
            'active' => true,
            'consultancy_id' => $consultancyA->id,
        ]);

        ClientCompany::create([
            'name' => 'Empresa Beta S.A.',
            'tax_id' => 'A33333333',
            'active' => true,
            'consultancy_id' => $consultancyA->id,
        ]);

        // ── Asesoría B ──────────────────────────────────────────────
        $consultancyB = Consultancy::create([
            'name' => 'Asesoría Martínez Fiscal',
            'tax_id' => 'B44444444',
            'active' => true,
        ]);

        User::create([
            'name' => 'Admin Martínez',
            'email' => 'admin@martinez.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'consultancy_id' => $consultancyB->id,
        ]);

        User::create([
            'name' => 'Consultor Martínez',
            'email' => 'consultor@martinez.com',
            'password' => 'password',
            'role' => UserRole::Consultant,
            'consultancy_id' => $consultancyB->id,
        ]);

        ClientCompany::create([
            'name' => 'Empresa Gamma S.L.',
            'tax_id' => 'B55555555',
            'active' => true,
            'consultancy_id' => $consultancyB->id,
        ]);

        ClientCompany::create([
            'name' => 'Empresa Delta S.A.',
            'tax_id' => 'A66666666',
            'active' => true,
            'consultancy_id' => $consultancyB->id,
        ]);
    }
}
