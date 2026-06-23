<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'in_app_enabled',
        'email_enabled',
        'whatsapp_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public static function isEmailEnabled(User $user, string $type): bool
    {
        $preference = static::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        return $preference?->email_enabled ?? true;
    }

    public static function isInAppEnabled(User $user, string $type): bool
    {
        $preference = static::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        return $preference?->in_app_enabled ?? true;
    }

    public static function isWhatsAppEnabled(User $user, string $type): bool
    {
        if (! $user->phone) {
            return false;
        }

        $preference = static::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        return $preference?->whatsapp_enabled ?? false;
    }
}
