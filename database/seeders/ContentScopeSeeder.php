<?php

namespace Database\Seeders;

use App\Domain\ContentScopes\ContentScope;
use Illuminate\Database\Seeder;

class ContentScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scopes = [
            [
                'key' => 'lessons',
                'label' => 'Lessons',
                'model_class' => \App\Domain\Lessons\Lesson::class,
                'is_active' => true,
            ],
            [
                'key' => 'daily_tasks',
                'label' => 'Daily Tasks',
                'model_class' => \App\Domain\Tasks\DailyTask::class,
                'is_active' => true,
            ],
            [
                'key' => 'duas',
                'label' => 'Duas',
                'model_class' => \App\Domain\Duas\Dua::class,
                'is_active' => true,
            ],
        ];

        foreach ($scopes as $scope) {
            ContentScope::updateOrCreate(
                ['key' => $scope['key']],
                $scope
            );
        }
    }
}
