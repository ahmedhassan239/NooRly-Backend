<?php

namespace App\Domain\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'provider',
        'provider_user_id',
        'email',
        'password',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    /**
     * Same as user() but includes soft-deleted AppUser records.
     * Used during registration to detect previously deleted accounts.
     */
    public function userWithTrashed()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id')->withTrashed();
    }
}
