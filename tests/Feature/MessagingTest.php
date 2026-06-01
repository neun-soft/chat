<?php

use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Event;

function makeWorkspaceWithChannel(): array
{
    $workspace = Workspace::create(['name' => 'Project X']);
    $channel = $workspace->channels()->create(['name' => 'General']);

    return [$workspace, $channel];
}

test('a channel member can list and post messages', function () {
    [$workspace, $channel] = makeWorkspaceWithChannel();
    $user = User::factory()->create();
    $workspace->members()->attach($user, ['role' => 'member']);
    $channel->members()->attach($user);

    $this->actingAs($user)
        ->getJson("/api/channels/{$channel->id}/messages")
        ->assertOk()
        ->assertJsonStructure(['messages']);

    $this->actingAs($user)
        ->postJson("/api/channels/{$channel->id}/messages", ['body' => 'Hello'])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Hello');

    expect($channel->messages()->count())->toBe(1);
});

test('a non-member cannot read or post to a channel', function () {
    [$workspace, $channel] = makeWorkspaceWithChannel();
    $outsider = User::factory()->create(); // not in workspace or channel

    $this->actingAs($outsider)
        ->getJson("/api/channels/{$channel->id}/messages")
        ->assertForbidden();

    $this->actingAs($outsider)
        ->postJson("/api/channels/{$channel->id}/messages", ['body' => 'Sneaky'])
        ->assertForbidden();

    expect($channel->messages()->count())->toBe(0);
});

test('an admin can access any channel', function () {
    [, $channel] = makeWorkspaceWithChannel();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->getJson("/api/channels/{$channel->id}/messages")
        ->assertOk();
});

test('workspaces index only returns workspaces the user belongs to', function () {
    [$mine] = makeWorkspaceWithChannel();
    [$theirs] = makeWorkspaceWithChannel();
    $user = User::factory()->create();
    $mine->members()->attach($user, ['role' => 'member']);

    $response = $this->actingAs($user)->getJson('/api/workspaces')->assertOk();

    $slugs = collect($response->json('workspaces'))->pluck('slug');
    expect($slugs)->toContain($mine->slug)
        ->not->toContain($theirs->slug);
});

test('posting a message broadcasts MessageSent', function () {
    Event::fake([MessageSent::class]);

    [$workspace, $channel] = makeWorkspaceWithChannel();
    $user = User::factory()->create();
    $workspace->members()->attach($user, ['role' => 'member']);
    $channel->members()->attach($user);

    $this->actingAs($user)
        ->postJson("/api/channels/{$channel->id}/messages", ['body' => 'Ping'])
        ->assertCreated();

    Event::assertDispatched(MessageSent::class);
});

test('non-admins cannot reach the admin console', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admins can create a workspace', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post('/admin/workspaces', ['name' => 'New Project'])
        ->assertRedirect();

    expect(Workspace::where('name', 'New Project')->exists())->toBeTrue();
});

test('admins can create a channel in a workspace (binds by id, not slug)', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    [$workspace] = makeWorkspaceWithChannel();

    $this->actingAs($admin)
        ->post("/admin/workspaces/{$workspace->id}/channels", ['name' => 'Roadmap'])
        ->assertRedirect();

    expect($workspace->channels()->where('name', 'Roadmap')->exists())->toBeTrue();
});

test('admins can add a member to a workspace by id', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    [$workspace] = makeWorkspaceWithChannel();
    $member = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/workspaces/{$workspace->id}/members", ['user_id' => $member->id])
        ->assertRedirect();

    expect($workspace->hasMember($member))->toBeTrue();
});
