<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Channel extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'topic',
    ];

    protected static function booted(): void
    {
        static::creating(function (Channel $channel) {
            if (empty($channel->slug)) {
                $channel->slug = static::uniqueSlug($channel->workspace_id, $channel->name);
            }
        });
    }

    public static function uniqueSlug(int $workspaceId, string $name): string
    {
        $base = Str::slug($name) ?: 'channel';
        $slug = $base;
        $i = 1;
        while (static::where('workspace_id', $workspaceId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }
}
