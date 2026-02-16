<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class AppUserOnboarding extends Model
{
    protected $table = 'app_user_onboarding';

    public const STEP_SHAHADA_DATE = 'shahada_date';
    public const STEP_GOALS = 'goals';
    public const STEP_SUMMARY = 'summary';
    public const STEP_DONE = 'done';

    public const STEPS_ORDER = [self::STEP_SHAHADA_DATE, self::STEP_GOALS, self::STEP_SUMMARY, self::STEP_DONE];

    protected $fillable = [
        'app_user_id',
        'start_date',
        'shahada_date',
        'learning_goal',
        'goals',
        'summary_completed',
        'current_step',
        'completed_at',
        'timezone',
    ];

    protected $casts = [
        'start_date' => 'date',
        'shahada_date' => 'date',
        'goals' => 'array',
        'summary_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Whether onboarding is fully completed (summary submitted).
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Compute the next logical step from current data.
     */
    public static function computeCurrentStep(array $data): string
    {
        if (!empty($data['summary_completed'])) {
            return self::STEP_DONE;
        }
        if (empty($data['shahada_date'])) {
            return self::STEP_SHAHADA_DATE;
        }
        if (empty($data['goals']) || !is_array($data['goals']) || count($data['goals']) === 0) {
            return self::STEP_GOALS;
        }
        return self::STEP_SUMMARY;
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
