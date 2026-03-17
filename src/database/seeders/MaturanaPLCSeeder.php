<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaturanaPLCSeeder extends Seeder
{
    public function run(): void
    {
        // ── Maturana Asociados ───────────────────────────────────────
        $maturana = Consultancy::updateOrCreate(
            ['tax_id' => 'B87654321'],
            ['name' => 'Maturana Asociados', 'active' => true],
        );

        User::updateOrCreate(
            ['email' => 'admin@maturana.com'],
            [
                'name' => 'Admin Maturana',
                'password' => 'password',
                'role' => UserRole::Admin,
                'consultancy_id' => $maturana->id,
            ],
        );

        User::updateOrCreate(
            ['email' => 'consultor@maturana.com'],
            [
                'name' => 'Consultor Maturana',
                'password' => 'password',
                'role' => UserRole::Consultant,
                'consultancy_id' => $maturana->id,
            ],
        );

        // ── PLC Consultores ──────────────────────────────────────────
        $plc = Consultancy::updateOrCreate(
            ['tax_id' => 'B12398765'],
            ['name' => 'PLC Consultores', 'active' => true],
        );

        User::updateOrCreate(
            ['email' => 'admin@plcconsultores.com'],
            [
                'name' => 'Admin PLC',
                'password' => 'password',
                'role' => UserRole::Admin,
                'consultancy_id' => $plc->id,
            ],
        );

        User::updateOrCreate(
            ['email' => 'consultor@plcconsultores.com'],
            [
                'name' => 'Consultor PLC',
                'password' => 'password',
                'role' => UserRole::Consultant,
                'consultancy_id' => $plc->id,
            ],
        );
    }
}
