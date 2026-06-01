<?php

use App\Models\Channel;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

// Private message channel — only members of the channel may subscribe.
Broadcast::channel('channel.{channelId}', function (User $user, int $channelId) {
    $channel = Channel::find($channelId);

    return $channel && ($user->isAdmin() || $channel->hasMember($user));
});

// Presence channel per workspace — drives the online roster.
Broadcast::channel('workspace.{workspaceId}', function (User $user, int $workspaceId) {
    $workspace = Workspace::find($workspaceId);

    if (! $workspace || ! ($user->isAdmin() || $workspace->hasMember($user))) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
