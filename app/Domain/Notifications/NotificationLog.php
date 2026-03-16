<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'user_id',
        'category',
        'sub_type',
        'channel',
        'delivery_status',
        'title',
        'body',
        'locale',
        'payload',
        'scheduled_for',
        'delivered_at',
        'opened_at',
        'suppression_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_for' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function markShown(): void
    {
        $this->update([
            'delivery_status' => 'shown',
            'delivered_at' => now(),
        ]);
    }

    public function markOpened(): void
    {
        $this->update([
            'delivery_status' => 'opened',
            'opened_at' => now(),
        ]);
    }

    public function markSuppressed(string $reason): void
    {
        $this->update([
            'delivery_status' => 'suppressed',
            'suppression_reason' => $reason,
        ]);
    }
}
