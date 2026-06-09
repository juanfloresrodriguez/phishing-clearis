<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignEvent extends Model
{
    protected $fillable = [
        'campaign_id', 'campaign_recipient_id', 'target_user_id',
        'event_type', 'ip_address', 'user_agent', 'device_type',
        'browser', 'os', 'country', 'city', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient()
    {
        return $this->belongsTo(CampaignRecipient::class, 'campaign_recipient_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(TargetUser::class);
    }
}
