<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        $org = auth()->user()->organization;
        $templates = EmailTemplate::where('organization_id', $org->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('EmailTemplates/Index', compact('templates'));
    }

    public function create(): Response
    {
        return Inertia::render('EmailTemplates/Create', [
            'categories' => EmailTemplate::$categories ?? [
                'hr', 'it', 'billing', 'shipping', 'google_workspace',
                'microsoft', 'internal_vendor', 'security_notice', 'custom',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $data = $this->validateTemplate($request);
        $data['organization_id'] = $org->id;
        $data['created_by'] = auth()->id();

        $template = EmailTemplate::create($data);
        AuditLog::record('template.create', $template);

        return redirect()->route('email-templates.show', $template)->with('success', 'Template created.');
    }

    public function show(EmailTemplate $emailTemplate): Response
    {
        $this->authorizeOrg($emailTemplate);
        return Inertia::render('EmailTemplates/Show', ['template' => $emailTemplate]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        $this->authorizeOrg($emailTemplate);
        return Inertia::render('EmailTemplates/Edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorizeOrg($emailTemplate);
        $old = $emailTemplate->toArray();
        $data = $this->validateTemplate($request);
        $data['version'] = $emailTemplate->version + 1;

        $emailTemplate->update($data);
        AuditLog::record('template.update', $emailTemplate, $old, $emailTemplate->fresh()->toArray());

        return redirect()->route('email-templates.show', $emailTemplate)->with('success', 'Template updated.');
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorizeOrg($emailTemplate);
        $request->validate(['to_email' => 'required|email']);

        // Only allow sending test to verified admin users in same org
        $toEmail = $request->to_email;
        $domain = substr(strrchr($toEmail, '@'), 1);
        $allowedDomains = auth()->user()->organization->verifiedDomains()->pluck('domain');

        if (!$allowedDomains->contains($domain)) {
            return back()->withErrors(['to_email' => 'Test email must be within your verified domains.']);
        }

        Mail::html($emailTemplate->html_content, function ($msg) use ($emailTemplate, $toEmail) {
            $msg->to($toEmail)->subject('[TEST] ' . $emailTemplate->subject);
        });

        AuditLog::record('template.send_test', $emailTemplate, [], ['to' => $toEmail]);

        return back()->with('success', "Test email sent to {$toEmail}.");
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorizeOrg($emailTemplate);
        $emailTemplate->delete();
        AuditLog::record('template.delete', $emailTemplate);
        return redirect()->route('email-templates.index')->with('success', 'Template deleted.');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name'                     => 'required|string|max:255',
            'subject'                  => 'required|string|max:255',
            'from_name'                => 'nullable|string|max:100',
            'html_content'             => 'required|string',
            'text_content'             => 'nullable|string',
            'category'                 => 'required|in:hr,it,billing,shipping,google_workspace,microsoft,internal_vendor,security_notice,custom',
            'language'                 => 'required|string|max:5',
            'has_attachment_simulation'=> 'boolean',
        ]);
    }

    private function authorizeOrg(EmailTemplate $template): void
    {
        abort_if($template->organization_id !== auth()->user()->organization_id, 403);
    }
}
