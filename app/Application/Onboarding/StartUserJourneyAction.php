<?php

namespace App\Application\Onboarding;

use App\Domain\Auth\AppUser;
use Illuminate\Support\Facades\DB;

class StartUserJourneyAction
{
    public function execute(AppUser $user, array $data): AppUser
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'shahada_date' => $data['shahada_date'] ?? null,
                'goal' => $data['goal'] ?? 'Learn Basics',
                'timezone' => $data['timezone'] ?? 'UTC',
                'current_day' => 1,
                'is_onboarded' => true,
            ]);

            // Initialize Streak
            $user->streak()->firstOrCreate([], [
                'current_streak' => 0,
                'max_streak' => 0,
                'last_activity_date' => null,
            ]);

            return $user->refresh();
        });
    }
}
