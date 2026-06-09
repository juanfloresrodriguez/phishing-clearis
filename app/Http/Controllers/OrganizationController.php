<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Services\DnsVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private DnsVerificationService $dns) {}

    public function show(): Response
    {
        $org = auth()->user()->organization()->with('domains', 'sendingProfiles')->first();
        return Inertia::render('Organization/Show', compact('org'));
    }

    public function update(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'timezone'                  => 'required|timezone',
            'work_hours'                => 'nullable|array',
            'event_retention_days'      => 'integer|min:0|max:3650',
            'anonymize_after_retention' => 'boolean',
            'privacy_notice'            => 'nullable|string|max:5000',
        ]);

        $old = $org->toArray();
        $org->update($data);
        AuditLog::record('organization.update', $org, $old, $org->fresh()->toArray());

        return back()->with('success', 'Organization updated.');
    }

    public function addDomain(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $request->validate(['domain' => 'required|string|regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i']);

        $domain = OrganizationDomain::firstOrCreate(
            ['organization_id' => $org->id, 'domain' => strtolower($request->domain)],
        );

        $token = $this->dns->generateVerificationToken($domain);
        AuditLog::record('domain.add', $domain);

        return back()->with('success', "Add TXT record: {$token}");
    }

    public function verifyDomain(OrganizationDomain $domain): RedirectResponse
    {
        abort_if($domain->organization_id !== auth()->user()->organization_id, 403);

        $verified = $this->dns->verifyDomain($domain);
        AuditLog::record('domain.verify', $domain, [], ['verified' => $verified]);

        return back()->with($verified ? 'success' : 'error',
            $verified ? 'Domain verified.' : 'Verification record not found. Check DNS propagation.');
    }

    public function removeDomain(OrganizationDomain $domain): RedirectResponse
    {
        abort_if($domain->organization_id !== auth()->user()->organization_id, 403);
        AuditLog::record('domain.remove', $domain);
        $domain->delete();
        return back()->with('success', 'Domain removed.');
    }
}
