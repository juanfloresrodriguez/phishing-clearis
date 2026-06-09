<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo_url', 'timezone', 'work_hours', 'settings',
        'privacy_notice', 'event_retention_days', 'anonymize_after_retention', 'is_active',
    ];

    protected $casts = [
        'work_hours' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'anonymize_after_retention' => 'boolean',
    ];

    public function domains()
    {
        return $this->hasMany(OrganizationDomain::class);
    }

    public function verifiedDomains()
    {
        return $this->hasMany(OrganizationDomain::class)->where('is_verified', true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function targetUsers()
    {
        return $this->hasMany(TargetUser::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function landingPages()
    {
        return $this->hasMany(LandingPage::class);
    }

    public function sendingProfiles()
    {
        return $this->hasMany(SendingProfile::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
