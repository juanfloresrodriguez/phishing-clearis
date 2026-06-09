<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class TargetUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'first_name'      => $this->faker->firstName(),
            'last_name'       => $this->faker->lastName(),
            'email'           => $this->faker->unique()->safeEmail(),
            'department'      => $this->faker->randomElement(['Engineering', 'Finance', 'HR', 'Marketing']),
            'language'        => 'es',
            'is_active'       => true,
            'excluded'        => false,
        ];
    }
}
