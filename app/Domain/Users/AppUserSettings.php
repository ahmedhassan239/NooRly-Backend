<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class AppUserSettings extends Model
{
    protected $table = 'app_user_settings';

    protected $fillable = [
        'app_user_id',
        'language',
        'dark_mode',
        'notifications_enabled',
        'time_format',
        'location_mode',
        'manual_city',
        'manual_country',
        'prayer_calc_method',
        'prayer_madhab',
        'prayer_adjustments',
    ];

    protected $casts = [
        'dark_mode' => 'boolean',
        'notifications_enabled' => 'boolean',
        'prayer_adjustments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
