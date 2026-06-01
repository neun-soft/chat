<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChannelAdminController extends Controller
{
    public function store(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'topic' => ['nullable', 'string', 'max:255'],
            'member_ids' => ['array'],
            'member_ids.*' => [Rule::exists('users', 'id')],
        ]);

        $channel = $workspace->channels()->create([
            'name' => $validated['name'],
            'topic' => $validated['topic'] ?? null,
        ]);

        // Members must belong to the workspace to be added to its channels.
        $memberIds = $this->workspaceMemberIds($workspace, $validated['member_ids'] ?? []);
        $channel->members()->sync($memberIds);

        return back()->with('success', "Channel #{$channel->slug} created.");
    }

    public function update(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'topic' => ['nullable', 'string', 'max:255'],
        ]);

        $channel->update($validated);

        return back()->with('success', 'Channel updated.');
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();

        return back()->with('success', 'Channel deleted.');
    }

    public function syncMembers(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'member_ids' => ['array'],
            'member_ids.*' => [Rule::exists('users', 'id')],
        ]);

        $memberIds = $this->workspaceMemberIds($channel->workspace, $validated['member_ids'] ?? []);
        $channel->members()->sync($memberIds);

        return back()->with('success', 'Channel membership updated.');
    }

    /**
     * Restrict a requested member list to users who actually belong to the workspace.
     *
     * @param  array<int>  $requested
     * @return array<int>
     */
    private function workspaceMemberIds(Workspace $workspace, array $requested): array
    {
        if (empty($requested)) {
            return [];
        }

        return $workspace->members()
            ->whereIn('users.id', $requested)
            ->pluck('users.id')
            ->all();
    }
}
