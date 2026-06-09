<?php

use App\Jobs\DispatchCampaignEmails;
use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Organization;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ─── Campaign dispatcher: runs every minute ────────────────────────────────
Schedule::call(function () {
    $campaigns = Campaign::where('status', 'running')->get();
    foreach ($campaigns as $campaign) {
        DispatchCampaignEmails::dispatch($campaign)->onQueue('campaigns');
    }
})->everyMinute()->name('dispatch-campaign-emails')->withoutOverlapping();

// ─── Auto-launch scheduled campaigns ──────────────────────────────────────
Schedule::call(function () {
    Campaign::where('status', 'scheduled')
        ->where('scheduled_start_at', '<=', now())
        ->each(function ($campaign) {
            $campaign->update(['status' => 'running', 'started_at' => now()]);
            DispatchCampaignEmails::dispatch($campaign)->onQueue('campaigns');
        });
})->everyMinute()->name('auto-launch-campaigns')->withoutOverlapping();

// ─── Auto-finish expired campaigns ────────────────────────────────────────
Schedule::call(function () {
    Campaign::where('status', 'running')
        ->whereNotNull('scheduled_end_at')
        ->where('scheduled_end_at', '<', now())
        ->each(fn($c) => $c->update(['status' => 'finished', 'finished_at' => now()]));
})->everyFiveMinutes()->name('finish-expired-campaigns');

// ─── Event retention cleanup ───────────────────────────────────────────────
Schedule::call(function () {
    Organization::where('event_retention_days', '>', 0)->each(function ($org) {
        $cutoff = now()->subDays($org->event_retention_days);
        CampaignEvent::whereHas('campaign', fn($q) => $q->where('organization_id', $org->id))
            ->where('occurred_at', '<', $cutoff)
            ->delete();
    });
})->daily()->name('event-retention-cleanup');
