<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\TargetUser;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $org = auth()->user()->organization;

        $stats = [
            'total_campaigns'  => Campaign::where('organization_id', $org->id)->count(),
            'active_campaigns' => Campaign::where('organization_id', $org->id)
                ->whereIn('status', ['running', 'scheduled'])->count(),
            'total_targets'    => TargetUser::where('organization_id', $org->id)
                ->where('is_active', true)->count(),
            'total_events'     => CampaignEvent::whereHas('campaign', fn($q) =>
                $q->where('organization_id', $org->id))->count(),
        ];

        $recentCampaigns = Campaign::where('organization_id', $org->id)
            ->with(['emailTemplate', 'sendingProfile'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($c) => array_merge($c->toArray(), ['stats' => $c->stats()]));

        $eventsByType = CampaignEvent::whereHas('campaign', fn($q) =>
            $q->where('organization_id', $org->id))
            ->where('occurred_at', '>=', now()->subDays(30))
            ->selectRaw('event_type, count(*) as cnt')
            ->groupBy('event_type')
            ->pluck('cnt', 'event_type');

        return Inertia::render('Dashboard', compact('stats', 'recentCampaigns', 'eventsByType'));
    }
}
