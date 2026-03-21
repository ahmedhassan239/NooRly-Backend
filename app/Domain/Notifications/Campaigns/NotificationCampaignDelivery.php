<?php

namespace App\Domain\Notifications\Campaigns;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotificationCampaignDelivery extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'platform',
        'provider',
        'provider_message_id',
        'delivery_status',
        'failure_reason',
        'sent_at',
        'opened_at',
        'shown_locally_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'shown_locally_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NotificationCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function inboxMessage(): HasOne
    {
        return $this->hasOne(NotificationInbox::class, 'delivery_id');
    }
}
