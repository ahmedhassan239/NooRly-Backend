<?php

namespace Database\Seeders;

use App\Domain\Auth\AppUser;
use App\Domain\Progress\UserProgress;
use Illuminate\Database\Seeder;

class UserProgressSeeder extends Seeder
{
    public function run(): void
    {
        $users = AppUser::take(2)->get();

        foreach ($users as $user) {
            UserProgress::create([
                'app_user_id' => $user->id,
                'date' => now()->toDateString(),
                'completed_task_ids' => ['task_1', 'task_2'],
                'salah_completed_step_ids' => ['salah_step_1'],
                'wudu_completed_step_ids' => ['wudu_step_1'],
                'streak_count' => 1,
            ]);

            UserProgress::create([
                'app_user_id' => $user->id,
                'date' => now()->subDay()->toDateString(),
                'completed_task_ids' => ['task_1'],
                'salah_completed_step_ids' => [],
                'wudu_completed_step_ids' => [],
                'streak_count' => 0,
            ]);
        }
    }
}
