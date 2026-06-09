<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDomain extends Model
{
    protected $fillable = [
        'organization_id', 'domain', 'verification_token',
        'is_verified', 'verified_at', 'allow_recipients', 'allow_sending',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'allow_recipients' => 'boolean',
        'allow_sending' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
