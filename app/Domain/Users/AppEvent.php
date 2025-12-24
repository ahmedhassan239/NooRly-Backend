<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class AppEvent extends Model
{
    protected $table = 'app_events';

    protected $fillable = [
        'app_user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
