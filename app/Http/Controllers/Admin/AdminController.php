<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * The admin console: every workspace with its channels + members, plus the
     * full user directory so you can create logins and assign people.
     */
    public function index()
    {
        $workspaces = Workspace::query()
            ->with(['channels.members:id,name,email', 'members:id,name,email'])
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $w) => [
                'id' => $w->id,
                'name' => $w->name,
                'slug' => $w->slug,
                'archived' => $w->archived,
                'members' => $w->members->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->pivot->role,
                ])->values(),
                'channels' => $w->channels->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'topic' => $c->topic,
                    'member_ids' => $c->members->pluck('id')->values(),
                ])->values(),
            ]);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => $u->is_admin,
            ]);

        return Inertia::render('admin/Index', [
            'workspaces' => $workspaces,
            'users' => $users,
        ]);
    }
}
