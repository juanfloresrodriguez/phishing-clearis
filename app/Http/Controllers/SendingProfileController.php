<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SendingProfile;
use App\Services\DnsVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SendingProfileController extends Controller
{
    public function __construct(private DnsVerificationService $dns) {}

    public function index(): Response
    {
        $org = $this->currentOrg();
        $profiles = SendingProfile::where('organization_id', $org->id)->get();
        return Inertia::render('SendingProfiles/Index', compact('profiles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $this->currentOrg();
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'from_name'      => 'required|string|max:100',
            'from_email'     => 'required|email',
            'reply_to'       => 'nullable|email',
            'mailer'         => 'required|in:smtp,google_relay,sendgrid,mailgun',
            'smtp_host'      => 'required|string|max:255',
            'smtp_port'      => 'required|integer',
            'smtp_username'  => 'nullable|string|max:255',
            'smtp_password'  => 'nullable|string|max:255',
            'smtp_encryption'=> 'required|in:tls,ssl,none',
        ]);

        // Verify from_email domain is authorized
        $domain = substr(strrchr($data['from_email'], '@'), 1);
        $allowed = $org->verifiedDomains()->where('allow_sending', true)->pluck('domain');
        if (!$allowed->contains($domain)) {
            return back()->withErrors(['from_email' => "Domain '{$domain}' is not authorized for sending. Verify domain first and enable sending."])->withInput();
        }

        $data['organization_id'] = $org->id;
        $profile = SendingProfile::create($data);

        // Immediately check DNS
        $this->dns->checkSendingProfileDns($profile);

        AuditLog::record('sending_profile.create', $profile);

        return redirect()->route('sending-profiles.index')->with('success', 'Sending profile created.');
    }

    public function checkDns(SendingProfile $sendingProfile): RedirectResponse
    {
        abort_if($sendingProfile->organization_id !== auth()->user()->organization_id, 403);
        $results = $this->dns->checkSendingProfileDns($sendingProfile);
        $warnings = $sendingProfile->fresh()->dnsWarnings();

        return back()->with(
            empty($warnings) ? 'success' : 'warning',
            empty($warnings) ? 'SPF, DKIM and DMARC all OK.' : implode('; ', $warnings)
        );
    }

    public function verify(SendingProfile $sendingProfile): RedirectResponse
    {
        abort_if($sendingProfile->organization_id !== auth()->user()->organization_id, 403);
        $sendingProfile->update(['is_verified' => true]);
        AuditLog::record('sending_profile.verify', $sendingProfile);
        return back()->with('success', 'Profile marked as verified.');
    }

    public function destroy(SendingProfile $sendingProfile): RedirectResponse
    {
        abort_if($sendingProfile->organization_id !== auth()->user()->organization_id, 403);
        $sendingProfile->delete();
        AuditLog::record('sending_profile.delete', $sendingProfile);
        return back()->with('success', 'Profile deleted.');
    }
}
