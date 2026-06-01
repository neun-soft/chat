<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceAdminController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.index');
    }

    public function show(Workspace $workspace)
    {
        return redirect()->route('admin.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $workspace = Workspace::create(['name' => $validated['name']]);

        // The operator is implicitly a member/owner of every workspace.
        $workspace->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => 'owner'],
        ]);

        return back()->with('success', "Workspace “{$workspace->name}” created.");
    }

    public function update(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'archived' => ['sometimes', 'boolean'],
        ]);

        $workspace->update($validated);

        return back()->with('success', 'Workspace updated.');
    }

    public function addMember(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
            'role' => ['sometimes', Rule::in(['owner', 'member'])],
        ]);

        $workspace->members()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role'] ?? 'member'],
        ]);

        return back()->with('success', 'Member added to workspace.');
    }

    public function removeMember(Workspace $workspace, User $user)
    {
        $workspace->members()->detach($user->id);

        // Also drop them from every channel in this workspace.
        $channelIds = $workspace->channels()->pluck('id');
        $user->channels()->detach($channelIds);

        return back()->with('success', 'Member removed from workspace.');
    }
}
