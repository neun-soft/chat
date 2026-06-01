<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'channel_id',
        'user_id',
        'body',
        'image_path',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Shape sent to the client (API responses + broadcast payload).
     *
     * @return array<string, mixed>
     */
    public function toClientArray(): array
    {
        $this->loadMissing('user:id,name');

        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'body' => $this->body,
            'image_url' => $this->imageUrl(),
            'created_at' => $this->created_at?->toISOString(),
            'user' => ['id' => $this->user->id, 'name' => $this->user->name],
        ];
    }
}
