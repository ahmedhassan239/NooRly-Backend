<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores push notification device tokens.
 *
 * Currently no push provider is integrated.
 * When FCM/APNs/OneSignal is added, set $provider and implement
 * NotificationChannelInterface with a real send() implementation.
 */
class UserNotificationToken extends Model
{
    protected $table = 'user_notification_tokens';

    protected $fillable = [
        'user_id',
        'platform',
        'token',
        'provider',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function markSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
