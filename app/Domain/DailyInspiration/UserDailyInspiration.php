<?php

namespace App\Domain\DailyInspiration;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the current daily inspiration for a user (one row per user).
 *
 * @property int $id
 * @property int $app_user_id
 * @property string $type hadith|verse|dua|adhkar
 * @property int $item_id
 * @property \Carbon\Carbon $selected_at
 * @property \Carbon\Carbon $expires_at
 * @property int|null $previous_item_id
 */
class UserDailyInspiration extends Model
{
    protected $table = 'user_daily_inspirations';

    protected $fillable = [
        'app_user_id',
        'type',
        'item_id',
        'selected_at',
        'expires_at',
        'previous_item_id',
    ];

    protected $casts = [
        'selected_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
