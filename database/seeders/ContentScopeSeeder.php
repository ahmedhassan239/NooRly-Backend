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
                'show_in_library_tabs' => false,
                'display_order' => 0,
                'icon_key' => 'book-open',
                'icon_color' => '#1E40AF',
            ],
            [
                'key' => 'daily_tasks',
                'label' => 'Daily Tasks',
                'model_class' => \App\Domain\Tasks\DailyTask::class,
                'is_active' => true,
                'show_in_library_tabs' => false,
                'display_order' => 1,
                'icon_key' => 'clipboard-list',
                'icon_color' => '#10B981',
            ],
            [
                'key' => 'duas',
                'label' => 'Duas',
                'model_class' => \App\Domain\Duas\Dua::class,
                'is_active' => true,
                'show_in_library_tabs' => true,
                'display_order' => 2,
                'icon_key' => 'book-marked',
                'icon_color' => '#8B5CF6',
            ],
            [
                'key' => 'hadith',
                'label' => 'Hadith',
                'model_class' => null,
                'is_active' => true,
                'show_in_library_tabs' => true,
                'display_order' => 3,
                'icon_key' => 'quote',
                'icon_color' => '#0EA5E9',
            ],
            [
                'key' => 'verses',
                'label' => 'Verses',
                'model_class' => null,
                'is_active' => true,
                'show_in_library_tabs' => true,
                'display_order' => 4,
                'icon_key' => 'book-open',
                'icon_color' => '#10B981',
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
