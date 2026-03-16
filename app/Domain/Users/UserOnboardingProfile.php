<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboardingProfile extends Model
{
    protected $table = 'user_onboarding_profiles';

    public const EMBRACE_ISLAM_RANGE = [
        'less_than_1_month',
        'months_1_to_6',
        'months_6_to_12',
        'years_1_to_2',
        'over_2_years',
        'born_muslim',
    ];

    public const ARABIC_LEVEL = [
        'fluent',
        'slow',
        'learning_now',
        'wants_to_learn',
        'none',
    ];

    public const PRAYER_LEVEL = [
        'regular',
        'not_all_5',
        'learning',
        'not_yet',
    ];

    public const QURAN_READING_LEVEL = [
        'regular',
        'occasional',
        'just_started',
        'not_yet',
    ];

    public const GOALS = [
        'learn_basics',
        'improve_prayer',
        'understand_quran',
        'build_good_habits',
        'connect_with_community',
    ];

    public const CHALLENGES = [
        'understanding_arabic',
        'remembering_to_pray',
        'finding_time',
        'staying_consistent',
        'dealing_with_doubts',
        'lack_of_support',
    ];

    public const DAILY_TIME = [
        'min_5_10',
        'min_15_20',
        'min_30_plus',
        'flexible',
    ];

    public const PREFERRED_LEARNING_TIME = [
        'morning',
        'afternoon',
        'evening',
        'night',
        'anytime',
    ];

    public const LEARNING_STYLE = [
        'reading',
        'listening',
        'videos',
        'interactive',
        'mix',
    ];

    public const REMINDER_PREFERENCE = [
        'all_reminders',
        'prayer_only',
        'customize_later',
        'none',
    ];

    protected $fillable = [
        'app_user_id',
        'display_name',
        'embrace_islam_range',
        'arabic_level',
        'prayer_level',
        'quran_reading_level',
        'goals',
        'challenges',
        'daily_time',
        'preferred_learning_time',
        'learning_style',
        'reminder_preference',
        'islam_date',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'goals' => 'array',
        'challenges' => 'array',
        'islam_date' => 'date',
        'onboarding_completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function isCompleted(): bool
    {
        return $this->onboarding_completed_at !== null;
    }
}
