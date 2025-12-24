<?php

namespace App\Domain\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'name',
        'gender',
        'birth_date',
        'locale',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
