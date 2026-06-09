<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name'            => $this->faker->words(3, true),
            'subject'         => $this->faker->sentence(),
            'html_content'    => '<p>Hello {{first_name}}, <a href="{{landing_url}}">click here</a>. {{tracking_pixel}}</p>',
            'category'        => 'custom',
            'language'        => 'es',
            'version'         => 1,
            'is_active'       => true,
        ];
    }
}
