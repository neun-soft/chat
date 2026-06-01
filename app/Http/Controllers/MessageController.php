<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'messages' => $messages->map(fn ($m) => $m->toClientArray()),
        ]);
    }

    public function store(Request $request, Channel $channel)
    {
        $this->authorizeChannel($request, $channel);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'], // 5MB
        ]);

        // A message must carry text, an image, or both.
        if (blank($validated['body'] ?? null) && ! $request->hasFile('image')) {
            throw ValidationException::withMessages([
                'body' => 'Type a message or attach an image.',
            ]);
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('attachments', 'public')
            : null;

        $message = $channel->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'] ?? '',
            'image_path' => $imagePath,
        ]);

        // Real-time fan-out is best-effort: the message is already persisted, so
        // a Reverb outage must not fail the send. Clients also load history on open.
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['message' => $message->toClientArray()], 201);
    }

    private function authorizeChannel(Request $request, Channel $channel): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $channel->hasMember($user), 403);
    }
}
