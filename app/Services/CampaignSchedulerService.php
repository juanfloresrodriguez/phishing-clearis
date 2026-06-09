<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\CampaignRecipient;
use App\Models\Group;
use App\Models\TargetUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CampaignSchedulerService
{
    public function buildRecipientList(Campaign $campaign): Collection
    {
        $excludedIds = $campaign->excluded_user_ids ?? [];

        $users = collect();
        foreach ($campaign->groups as $group) {
            $users = $users->merge($group->resolveMembers());
        }

        return $users
            ->unique('id')
            ->filter(fn($u) => !in_array($u->id, $excludedIds));
    }

    public function scheduleRecipients(Campaign $campaign): int
    {
        $users = $this->buildRecipientList($campaign);
        $start = $campaign->scheduled_start_at ?? now();
        $end = $campaign->scheduled_end_at ?? $start->copy()->addDays(3);

        $created = 0;
        foreach ($users as $user) {
            if (CampaignRecipient::where('campaign_id', $campaign->id)
                ->where('target_user_id', $user->id)->exists()) {
                continue;
            }

            $scheduledAt = $this->randomTimeInWindow($start, $end, $campaign);

            $recipient = CampaignRecipient::create([
                'campaign_id'    => $campaign->id,
                'target_user_id' => $user->id,
                'scheduled_at'   => $scheduledAt,
            ]);

            CampaignEvent::create([
                'campaign_id'           => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'target_user_id'        => $user->id,
                'event_type'            => 'email_scheduled',
                'occurred_at'           => now(),
            ]);

            $created++;
        }

        return $created;
    }

    private function randomTimeInWindow(Carbon $start, Carbon $end, Campaign $campaign): Carbon
    {
        $attempts = 0;
        do {
            $ts = Carbon::createFromTimestamp(
                rand($start->timestamp, $end->timestamp)
            );

            if ($campaign->respect_work_days && $ts->isWeekend()) {
                $attempts++;
                continue;
            }

            if ($campaign->respect_work_hours && $campaign->send_window_start && $campaign->send_window_end) {
                $wStart = Carbon::parse($ts->toDateString() . ' ' . $campaign->send_window_start);
                $wEnd = Carbon::parse($ts->toDateString() . ' ' . $campaign->send_window_end);
                if ($ts->lt($wStart) || $ts->gt($wEnd)) {
                    $attempts++;
                    continue;
                }
            }

            return $ts;
        } while ($attempts < 50);

        // fallback: use start time
        return $start->copy()->addMinutes(rand(1, 60));
    }
}
