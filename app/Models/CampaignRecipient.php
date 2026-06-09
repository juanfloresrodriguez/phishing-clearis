<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id', 'target_user_id', 'tracking_token',
        'scheduled_at', 'sent_at', 'delivered_at',
        'send_error', 'send_error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'send_error' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->tracking_token)) {
                $model->tracking_token = Str::random(40);
            }
        });
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(TargetUser::class);
    }

    public function events()
    {
        return $this->hasMany(CampaignEvent::class);
    }
}
