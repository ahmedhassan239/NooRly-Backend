<?php

namespace App\Domain\Notifications\Campaigns;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationInbox extends Model
{
    protected $table = 'notification_inbox';

    protected $fillable = [
        'user_id',
        'campaign_id',
        'delivery_id',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'route',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NotificationCampaign::class, 'campaign_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(NotificationCampaignDelivery::class, 'delivery_id');
    }
}
