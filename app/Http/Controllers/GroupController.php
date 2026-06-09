<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\TargetUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $org = auth()->user()->organization;
        $groups = Group::where('organization_id', $org->id)
            ->withCount('targetUsers')
            ->get();

        return Inertia::render('Groups/Index', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string|max:500',
            'type'            => 'required|in:static,dynamic',
            'dynamic_filters' => 'nullable|array',
            'user_ids'        => 'nullable|array',
            'user_ids.*'      => 'exists:target_users,id',
        ]);

        $group = Group::create([
            'organization_id' => $org->id,
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'],
            'dynamic_filters' => $data['dynamic_filters'] ?? null,
        ]);

        if ($data['type'] === 'static' && !empty($data['user_ids'])) {
            // Only allow users from same org
            $validIds = TargetUser::where('organization_id', $org->id)
                ->whereIn('id', $data['user_ids'])->pluck('id');
            $group->targetUsers()->sync($validIds);
        }

        return back()->with('success', 'Group created.');
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        abort_if($group->organization_id !== auth()->user()->organization_id, 403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $group->update($data);
        return back()->with('success', 'Group updated.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        abort_if($group->organization_id !== auth()->user()->organization_id, 403);
        $group->delete();
        return back()->with('success', 'Group deleted.');
    }
}
