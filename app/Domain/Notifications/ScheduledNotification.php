<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    protected $table = 'scheduled_notifications';

    protected $fillable = [
        'user_id',
        'category',
        'sub_type',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'scheduled_for',
        'status',
        'payload',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('scheduled_for', '<=', now());
    }

    public function markProcessed(): void
    {
        $this->update(['status' => 'processed']);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Resolve localized title.
     */
    public function titleForLocale(string $locale): ?string
    {
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    /**
     * Resolve localized body.
     */
    public function bodyForLocale(string $locale): ?string
    {
        return $locale === 'ar' ? $this->body_ar : $this->body_en;
    }
}
