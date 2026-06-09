<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\SendingProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();
        return [
            'organization_id'    => $org->id,
            'email_template_id'  => EmailTemplate::factory(['organization_id' => $org->id]),
            'sending_profile_id' => SendingProfile::factory(['organization_id' => $org->id]),
            'name'               => 'Test Campaign ' . $this->faker->word(),
            'subject'            => 'Test Subject',
            'status'             => 'draft',
            'rate_limit_per_minute' => 10,
            'language'           => 'es',
        ];
    }
}
