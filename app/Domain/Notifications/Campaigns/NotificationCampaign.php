<?php

namespace App\Domain\Notifications\Campaigns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'type',
        'audience_type',
        'audience_filters',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'route',
        'image_url',
        'priority',
        'send_mode',
        'scheduled_for',
        'status',
        'created_by',
        'sent_count',
        'failed_count',
        'skipped_count',
        'processed_at',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationCampaignDelivery::class, 'campaign_id');
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(NotificationInbox::class, 'campaign_id');
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['sent', 'partial', 'failed', 'cancelled'], true);
    }
}
