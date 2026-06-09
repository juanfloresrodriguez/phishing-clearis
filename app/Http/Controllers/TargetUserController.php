<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TargetUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Reader;

class TargetUserController extends Controller
{
    public function index(Request $request): Response
    {
        $org = $this->currentOrg();
        $users = TargetUser::where('organization_id', $org->id)
            ->when($request->search, fn($q, $s) => $q->where('email', 'like', "%{$s}%")
                ->orWhere('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%"))
            ->when($request->department, fn($q, $d) => $q->where('department', $d))
            ->orderBy('last_name')
            ->paginate(50)
            ->withQueryString();

        $departments = TargetUser::where('organization_id', $org->id)
            ->distinct()->pluck('department')->filter();

        return Inertia::render('TargetUsers/Index', compact('users', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $this->currentOrg();
        $data = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email',
            'department'  => 'nullable|string|max:100',
            'job_title'   => 'nullable|string|max:100',
            'office'      => 'nullable|string|max:100',
            'language'    => 'nullable|string|max:5',
        ]);

        $this->validateEmailDomain($data['email'], $org);

        TargetUser::updateOrCreate(
            ['organization_id' => $org->id, 'email' => $data['email']],
            array_merge($data, ['organization_id' => $org->id])
        );

        AuditLog::record('target_user.create');
        return back()->with('success', 'User added.');
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $org = $this->currentOrg();
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $csv = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $imported = 0;
        $errors = [];

        foreach ($csv->getRecords() as $row) {
            try {
                $email = trim($row['email'] ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $this->validateEmailDomain($email, $org);

                TargetUser::updateOrCreate(
                    ['organization_id' => $org->id, 'email' => $email],
                    [
                        'organization_id' => $org->id,
                        'first_name'      => trim($row['first_name'] ?? ''),
                        'last_name'       => trim($row['last_name'] ?? ''),
                        'email'           => $email,
                        'department'      => trim($row['department'] ?? '') ?: null,
                        'job_title'       => trim($row['job_title'] ?? '') ?: null,
                        'office'          => trim($row['office'] ?? '') ?: null,
                        'language'        => trim($row['language'] ?? 'es'),
                        'import_source'   => 'csv',
                    ]
                );
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        AuditLog::record('target_user.import_csv', null, [], ['imported' => $imported]);

        return back()->with('success', "{$imported} users imported.")
            ->withErrors(array_slice($errors, 0, 5));
    }

    public function destroy(TargetUser $targetUser): RedirectResponse
    {
        abort_if($targetUser->organization_id !== auth()->user()->organization_id, 403);
        $targetUser->delete();
        AuditLog::record('target_user.delete', $targetUser);
        return back()->with('success', 'User deleted.');
    }

    public function toggleExclude(TargetUser $targetUser): RedirectResponse
    {
        abort_if($targetUser->organization_id !== auth()->user()->organization_id, 403);
        $targetUser->update(['excluded' => !$targetUser->excluded]);
        return back()->with('success', 'User exclusion updated.');
    }

    private function validateEmailDomain(string $email, $org): void
    {
        $domain = substr(strrchr($email, '@'), 1);
        $allowed = $org->verifiedDomains()->where('allow_recipients', true)->pluck('domain');

        if (!$allowed->contains($domain)) {
            throw new \InvalidArgumentException("Email domain '{$domain}' is not authorized for this organization.");
        }
    }
}
