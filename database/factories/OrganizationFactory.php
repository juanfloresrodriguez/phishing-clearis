<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => $this->faker->company(),
            'slug'     => $this->faker->unique()->slug(),
            'timezone' => 'Europe/Madrid',
            'event_retention_days' => 365,
            'is_active' => true,
        ];
    }
}
