<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SendingProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name'            => 'Test Profile',
            'from_name'       => 'IT Security',
            'from_email'      => 'security@test.local',
            'mailer'          => 'smtp',
            'smtp_host'       => 'localhost',
            'smtp_port'       => 1025,
            'smtp_encryption' => 'none',
            'is_verified'     => true,
            'spf_ok'          => true,
            'dkim_ok'         => true,
            'dmarc_ok'        => true,
            'is_active'       => true,
        ];
    }
}
