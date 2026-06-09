<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Jobs\DispatchCampaignEmails;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\EmailTemplate;
use App\Models\Group;
use App\Models\LandingPage;
use App\Models\SendingProfile;
use App\Services\CampaignSchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(private CampaignSchedulerService $scheduler) {}

    public function index(): Response
    {
        $org = auth()->user()->organization;
        $campaigns = Campaign::where('organization_id', $org->id)
            ->with(['emailTemplate', 'sendingProfile'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($c) => array_merge($c->toArray(), ['stats' => $c->stats()]));

        return Inertia::render('Campaigns/Index', compact('campaigns'));
    }

    public function create(): Response
    {
        $org = auth()->user()->organization;
        return Inertia::render('Campaigns/Create', [
            'templates' => EmailTemplate::where('organization_id', $org->id)->where('is_active', true)->get(),
            'landingPages' => LandingPage::where('organization_id', $org->id)->where('is_active', true)->get(),
            'sendingProfiles' => SendingProfile::where('organization_id', $org->id)->where('is_active', true)->get(),
            'groups' => Group::where('organization_id', $org->id)->withCount('targetUsers')->get(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $data = $request->validated();
        $data['organization_id'] = $org->id;
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        $groupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        $campaign = Campaign::create($data);
        $campaign->groups()->sync($groupIds);

        AuditLog::record('campaign.create', $campaign, [], $campaign->toArray());

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function show(Campaign $campaign): Response
    {
        $this->authorizeOrg($campaign);

        $campaign->load(['emailTemplate', 'landingPage', 'sendingProfile', 'groups']);
        $stats = $campaign->stats();

        $eventTimeline = CampaignEvent::where('campaign_id', $campaign->id)
            ->selectRaw('date(occurred_at) as date, event_type, count(*) as cnt')
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get();

        $departmentStats = CampaignEvent::where('campaign_id', $campaign->id)
            ->whereIn('event_type', ['email_opened', 'link_clicked', 'form_submitted'])
            ->join('target_users', 'target_users.id', '=', 'campaign_events.target_user_id')
            ->selectRaw('target_users.department, campaign_events.event_type, count(*) as cnt')
            ->groupBy('target_users.department', 'campaign_events.event_type')
            ->get();

        return Inertia::render('Campaigns/Show', compact('campaign', 'stats', 'eventTimeline', 'departmentStats'));
    }

    public function edit(Campaign $campaign): Response
    {
        $this->authorizeOrg($campaign);
        abort_if(!in_array($campaign->status, ['draft', 'scheduled']), 403, 'Cannot edit a running campaign.');

        $org = auth()->user()->organization;
        $campaign->load('groups');

        return Inertia::render('Campaigns/Edit', [
            'campaign'        => $campaign,
            'templates'       => EmailTemplate::where('organization_id', $org->id)->where('is_active', true)->get(),
            'landingPages'    => LandingPage::where('organization_id', $org->id)->where('is_active', true)->get(),
            'sendingProfiles' => SendingProfile::where('organization_id', $org->id)->where('is_active', true)->get(),
            'groups'          => Group::where('organization_id', $org->id)->withCount('targetUsers')->get(),
        ]);
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOrg($campaign);
        abort_if(!in_array($campaign->status, ['draft', 'scheduled']), 403, 'Cannot edit a running campaign.');

        $data = $request->validated();
        $groupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        $old = $campaign->toArray();
        $campaign->update($data);
        $campaign->groups()->sync($groupIds);

        AuditLog::record('campaign.update', $campaign, $old, $campaign->fresh()->toArray());

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function launch(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOrg($campaign);
        abort_if(!in_array($campaign->status, ['draft', 'scheduled', 'paused']), 403, 'Cannot launch campaign in this state.');

        $profile = $campaign->sendingProfile;
        if (!$profile->is_verified) {
            return back()->withErrors(['profile' => 'Sending profile is not verified.']);
        }

        // Schedule recipients if not yet done
        if ($campaign->recipients()->count() === 0) {
            $this->scheduler->scheduleRecipients($campaign);
        }

        $campaign->update(['status' => 'running', 'started_at' => now()]);
        DispatchCampaignEmails::dispatch($campaign)->onQueue('campaigns');

        AuditLog::record('campaign.launch', $campaign);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign launched.');
    }

    public function pause(Campaign $campaign): RedirectResponse
    {
        $this->authorizeOrg($campaign);
        $campaign->update(['status' => 'paused', 'paused_at' => now()]);
        AuditLog::record('campaign.pause', $campaign);
        return back()->with('success', 'Campaign paused.');
    }

    public function cancel(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeOrg($campaign);
        $campaign->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->input('reason', 'Manual cancellation'),
        ]);
        AuditLog::record('campaign.cancel', $campaign);
        return back()->with('success', 'Campaign cancelled.');
    }

    public function dryRun(Campaign $campaign): RedirectResponse
    {
        $this->authorizeOrg($campaign);
        $this->scheduler->scheduleRecipients($campaign);
        $campaign->update(['status' => 'dry_run', 'is_dry_run' => true]);
        DispatchCampaignEmails::dispatch($campaign)->onQueue('campaigns');
        AuditLog::record('campaign.dry_run', $campaign);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Dry run started.');
    }

    public function exportCsv(Campaign $campaign): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeOrg($campaign);
        AuditLog::record('campaign.export_csv', $campaign);

        $recipients = $campaign->recipients()->with(['targetUser', 'events'])->get();

        return response()->streamDownload(function () use ($campaign, $recipients) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Email', 'Name', 'Department', 'Scheduled At', 'Sent At',
                'Opened', 'Clicked', 'Form Submitted', 'Reported', 'Send Error',
            ]);

            foreach ($recipients as $r) {
                $events = $r->events->pluck('event_type');
                fputcsv($out, [
                    $r->targetUser->email,
                    $r->targetUser->fullName(),
                    $r->targetUser->department,
                    $r->scheduled_at?->toDateTimeString(),
                    $r->sent_at?->toDateTimeString(),
                    $events->contains('email_opened') ? 'Yes' : 'No',
                    $events->contains('link_clicked') ? 'Yes' : 'No',
                    $events->contains('form_submitted') ? 'Yes' : 'No',
                    $events->contains('email_reported') ? 'Yes' : 'No',
                    $r->send_error ? 'Yes' : 'No',
                ]);
            }

            fclose($out);
        }, "campaign_{$campaign->id}_report.csv");
    }

    private function authorizeOrg(Campaign $campaign): void
    {
        abort_if($campaign->organization_id !== auth()->user()->organization_id, 403);
    }
}
