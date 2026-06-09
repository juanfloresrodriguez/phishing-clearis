<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'created_by', 'email_template_id', 'landing_page_id',
        'sending_profile_id', 'name', 'subject', 'description', 'status',
        'scheduled_start_at', 'scheduled_end_at', 'send_window_start', 'send_window_end',
        'respect_work_hours', 'respect_work_days', 'rate_limit_per_minute',
        'language', 'tags', 'excluded_user_ids', 'is_dry_run',
        'started_at', 'finished_at', 'paused_at', 'cancel_reason',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'paused_at' => 'datetime',
        'tags' => 'array',
        'excluded_user_ids' => 'array',
        'respect_work_hours' => 'boolean',
        'respect_work_days' => 'boolean',
        'is_dry_run' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function sendingProfile()
    {
        return $this->belongsTo(SendingProfile::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'campaign_groups');
    }

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function events()
    {
        return $this->hasMany(CampaignEvent::class);
    }

    public function isRunnable(): bool
    {
        return in_array($this->status, ['scheduled', 'paused']);
    }

    public function stats(): array
    {
        $total = $this->recipients()->count();
        $sent = $this->recipients()->whereNotNull('sent_at')->count();

        $eventCounts = $this->events()
            ->selectRaw('event_type, count(*) as cnt')
            ->groupBy('event_type')
            ->pluck('cnt', 'event_type');

        return [
            'total'           => $total,
            'sent'            => $sent,
            'opened'          => $eventCounts['email_opened'] ?? 0,
            'clicked'         => $eventCounts['link_clicked'] ?? 0,
            'form_submitted'  => $eventCounts['form_submitted'] ?? 0,
            'reported'        => $eventCounts['email_reported'] ?? 0,
            'open_rate'       => $sent > 0 ? round(($eventCounts['email_opened'] ?? 0) / $sent * 100, 1) : 0,
            'click_rate'      => $sent > 0 ? round(($eventCounts['link_clicked'] ?? 0) / $sent * 100, 1) : 0,
            'submit_rate'     => $sent > 0 ? round(($eventCounts['form_submitted'] ?? 0) / $sent * 100, 1) : 0,
        ];
    }
}
