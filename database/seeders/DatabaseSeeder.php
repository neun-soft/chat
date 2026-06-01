<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // You — the operator. Logs into everything.
        $admin = User::updateOrCreate(
            ['email' => 'diego@neunsoft.com'],
            [
                'name' => 'Diego',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // A sample client login.
        $client = User::updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Acme Client',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        // A sample project workspace with the client in it.
        $workspace = Workspace::firstOrCreate(['slug' => 'acme-redesign'], ['name' => 'Acme Redesign']);
        $workspace->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'owner'],
            $client->id => ['role' => 'member'],
        ]);

        foreach (['general', 'design', 'bugs'] as $name) {
            $channel = Channel::firstOrCreate(
                ['workspace_id' => $workspace->id, 'slug' => $name],
                ['name' => ucfirst($name)]
            );
            $channel->members()->syncWithoutDetaching([$admin->id, $client->id]);
        }

        $general = $workspace->channels()->where('slug', 'general')->first();
        if ($general && $general->messages()->count() === 0) {
            Message::create(['channel_id' => $general->id, 'user_id' => $admin->id, 'body' => 'Welcome to the Acme Redesign workspace! 👋']);
            Message::create(['channel_id' => $general->id, 'user_id' => $client->id, 'body' => 'Thanks Diego, excited to get started.']);
        }
    }
}
