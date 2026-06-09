<?php

namespace App\Models;

use Database\Factories\SendingProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SendingProfile extends Model
{
    /** @use HasFactory<SendingProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id', 'name', 'from_name', 'from_email', 'reply_to',
        'mailer', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'smtp_encryption', 'spf_ok', 'dkim_ok', 'dmarc_ok', 'dns_checked_at',
        'is_verified', 'is_active',
    ];

    protected $hidden = ['smtp_password'];

    protected $casts = [
        'spf_ok' => 'boolean',
        'dkim_ok' => 'boolean',
        'dmarc_ok' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'dns_checked_at' => 'datetime',
    ];

    public function setSmtpPasswordAttribute(?string $value): void
    {
        $this->attributes['smtp_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSmtpPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function dnsWarnings(): array
    {
        $warnings = [];
        if (!$this->spf_ok) $warnings[] = 'SPF not configured or invalid';
        if (!$this->dkim_ok) $warnings[] = 'DKIM not configured or invalid';
        if (!$this->dmarc_ok) $warnings[] = 'DMARC not configured or invalid';
        return $warnings;
    }
}
