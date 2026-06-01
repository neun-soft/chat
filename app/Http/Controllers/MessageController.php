<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Channel;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Channel $channel)
    {
        $this->authorizeChannel($request, $channel);

        // Cursor pagination, newest-last. `before` is a message id for backscroll.
        $query = $channel->messages()->with('user:id,name')->orderByDesc('id');

        if ($before = $request->integer('before')) {
            $query->where('id', '<', $before);
        }

        $messages = $query->limit(50)->get()->reverse()->values();

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'channel_id' => $m->channel_id,
                'body' => $m->body,
                'created_at' => $m->created_at?->toISOString(),
                'user' => ['id' => $m->user->id, 'name' => $m->user->name],
            ]),
        ]);
    }

    public function store(Request $request, Channel $channel)
    {
        $this->authorizeChannel($request, $channel);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $channel->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // Real-time fan-out is best-effort: the message is already persisted, so
        // a Reverb outage must not fail the send. Clients also load history on open.
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        $message->load('user:id,name');

        return response()->json([
            'message' => [
                'id' => $message->id,
                'channel_id' => $message->channel_id,
                'body' => $message->body,
                'created_at' => $message->created_at?->toISOString(),
                'user' => ['id' => $message->user->id, 'name' => $message->user->name],
            ],
        ], 201);
    }

    private function authorizeChannel(Request $request, Channel $channel): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $channel->hasMember($user), 403);
    }
}
