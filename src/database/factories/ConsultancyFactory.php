<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consultancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultancy>
 */
class ConsultancyFactory extends Factory
{
    public function definition(): array
    {
        return [
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
