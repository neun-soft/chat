<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    /**
     * Workspaces the current user can see, each with the channels they belong to.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $workspaces = $this->visibleWorkspaces($request)
            ->with(['channels' => function ($query) use ($user) {
                if (! $user->isAdmin()) {
                    $query->whereHas('members', fn ($q) => $q->whereKey($user->id));
                }
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'workspaces' => $workspaces->map(fn (Workspace $w) => $this->present($w)),
        ]);
    }

    public function show(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspace($request, $workspace);

        $user = $request->user();
        $workspace->load(['channels' => function ($query) use ($user) {
            if (! $user->isAdmin()) {
                $query->whereHas('members', fn ($q) => $q->whereKey($user->id));
            }
            $query->orderBy('name');
        }]);

        return response()->json(['workspace' => $this->present($workspace)]);
    }

    private function present(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'channels' => $workspace->channels->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'topic' => $c->topic,
            ])->values(),
        ];
    }

    private function visibleWorkspaces(Request $request)
    {
        $user = $request->user();

        return $user->isAdmin()
            ? Workspace::query()->where('archived', false)
            : $user->workspaces()->where('archived', false)->getQuery();
    }

    private function authorizeWorkspace(Request $request, Workspace $workspace): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $workspace->hasMember($user), 403);
    }
}
