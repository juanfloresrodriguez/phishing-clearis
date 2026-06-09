<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LandingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function index(): Response
    {
        $org = auth()->user()->organization;
        $pages = LandingPage::where('organization_id', $org->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('LandingPages/Index', compact('pages'));
    }

    public function create(): Response
    {
        return Inertia::render('LandingPages/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $data = $this->validatePage($request);
        $data['organization_id'] = $org->id;
        $data['created_by'] = auth()->id();
        $data['capture_credentials'] = false; // Always forced to false

        $page = LandingPage::create($data);
        AuditLog::record('landing_page.create', $page);

        return redirect()->route('landing-pages.show', $page)->with('success', 'Landing page created.');
    }

    public function show(LandingPage $landingPage): Response
    {
        $this->authorizeOrg($landingPage);
        return Inertia::render('LandingPages/Show', ['page' => $landingPage]);
    }

    public function edit(LandingPage $landingPage): Response
    {
        $this->authorizeOrg($landingPage);
        return Inertia::render('LandingPages/Edit', ['page' => $landingPage]);
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        $this->authorizeOrg($landingPage);
        $old = $landingPage->toArray();
        $data = $this->validatePage($request);
        $data['capture_credentials'] = false; // Always forced to false

        $landingPage->update($data);
        AuditLog::record('landing_page.update', $landingPage, $old);

        return redirect()->route('landing-pages.show', $landingPage)->with('success', 'Landing page updated.');
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        $this->authorizeOrg($landingPage);
        $landingPage->delete();
        AuditLog::record('landing_page.delete', $landingPage);
        return redirect()->route('landing-pages.index')->with('success', 'Landing page deleted.');
    }

    private function validatePage(Request $request): array
    {
        return $request->validate([
            'name'                      => 'required|string|max:255',
            'html_content'              => 'required|string',
            'training_html'             => 'nullable|string',
            'redirect_url'              => 'nullable|url',
            'language'                  => 'required|string|max:5',
            'show_training_after_submit'=> 'boolean',
        ]);
    }

    private function authorizeOrg(LandingPage $page): void
    {
        abort_if($page->organization_id !== auth()->user()->organization_id, 403);
    }
}
