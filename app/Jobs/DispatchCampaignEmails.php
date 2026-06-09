<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchCampaignEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public Campaign $campaign) {}

    public function handle(): void
    {
        if (!in_array($this->campaign->status, ['running', 'scheduled'])) {
            return;
        }

        $this->campaign->update(['status' => 'running', 'started_at' => now()]);

        $pending = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->whereNull('sent_at')
            ->where('send_error', false)
            ->where('scheduled_at', '<=', now())
            ->with(['targetUser', 'campaign.emailTemplate', 'campaign.sendingProfile'])
            ->get();

        $rateLimit = $this->campaign->rate_limit_per_minute;
        $sent = 0;

        foreach ($pending as $recipient) {
            // Check campaign is still running (could be paused/cancelled mid-loop)
            $this->campaign->refresh();
            if ($this->campaign->status !== 'running') break;

            SendPhishingEmail::dispatch($recipient)->onQueue('campaigns');
            $sent++;

            if ($rateLimit > 0 && $sent % $rateLimit === 0) {
                sleep(60);
            }
        }

        // Check if all recipients have been processed
        $remaining = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->whereNull('sent_at')
            ->where('send_error', false)
            ->where('scheduled_at', '>', now())
            ->count();

        if ($remaining === 0 && $pending->count() > 0) {
            $this->campaign->update(['status' => 'finished', 'finished_at' => now()]);
        }
    }
}
