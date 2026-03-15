<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClientCompany;
use App\Models\Consultancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientCompany>
 */
class ClientCompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consultancy_id' => Consultancy::factory(),
            'name' => fake()->company(),
            'tax_id' => strtoupper(fake()->bothify('?########?')),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
