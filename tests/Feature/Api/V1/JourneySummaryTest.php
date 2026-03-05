<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Journey\JourneyWeekTranslation;
use App\Domain\Lessons\Lesson;
use App\Domain\Lessons\LessonCompletion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_returns_200_with_expected_structure(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Week 1',
            'is_active' => true,
        ]);
        JourneyWeekTranslation::create([
            'journey_week_id' => $week->id,
            'language_code' => 'en',
            'title' => 'Week 1',
        ]);
        $lesson = Lesson::factory()->create(['title' => 'First', 'duration_minutes' => 5]);
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $lesson->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey/summary');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.day_index', 1)
            ->assertJsonPath('data.total_days', 90)
            ->assertJsonPath('data.streak_days', 0)
            ->assertJsonPath('data.active_weeks', 0)
            ->assertJsonPath('data.left_days', 89)
            ->assertJsonPath('data.completed_lessons', 0)
            ->assertJsonPath('data.total_lessons', 1)
            ->assertJsonPath('data.completion_percent', 0)
            ->assertJsonPath('data.current_lesson.lesson_id', $lesson->id)
            ->assertJsonPath('data.current_lesson.status', 'current')
            ->assertJsonCount(1, 'data.milestones');
        $this->assertSame('in_progress', $response->json('data.milestones.0.status'));
    }

    public function test_summary_after_completion_increments_day_and_completed_lessons(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Week 1',
            'is_active' => true,
        ]);
        $lesson = Lesson::factory()->create();
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $lesson->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);
        LessonCompletion::create([
            'app_user_id' => $user->id,
            'lesson_id' => (string) $lesson->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey/summary');

        $response->assertOk()
            ->assertJsonPath('data.completed_lessons', 1)
            ->assertJsonPath('data.day_index', 2)
            ->assertJsonPath('data.left_days', 88)
            ->assertJsonPath('data.current_lesson', null)
            ->assertJsonPath('data.milestones.0.status', 'completed');
    }

    public function test_summary_streak_days_from_completion_dates(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Week 1',
            'is_active' => true,
        ]);
        $l1 = Lesson::factory()->create();
        $l2 = Lesson::factory()->create();
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $l1->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $l2->id,
            'day_number' => 2,
            'position' => 1,
            'sort_order' => 201,
        ]);
        $today = Carbon::now()->timezone(config('app.timezone', 'UTC'));
        LessonCompletion::create([
            'app_user_id' => $user->id,
            'lesson_id' => (string) $l1->id,
            'completed_at' => $today->copy()->subDays(2),
        ]);
        LessonCompletion::create([
            'app_user_id' => $user->id,
            'lesson_id' => (string) $l2->id,
            'completed_at' => $today->copy()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey/summary');

        $response->assertOk();
        $streak = $response->json('data.streak_days');
        $this->assertGreaterThanOrEqual(2, $streak, 'Streak should count consecutive days with completions');
    }

    public function test_summary_never_returns_500_when_no_journey_data(): void
    {
        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey/summary');

        $response->assertOk();
        $this->assertNotEquals(500, $response->status());
        $response->assertJsonPath('data.day_index', 1)
            ->assertJsonPath('data.total_days', 90)
            ->assertJsonPath('data.milestones', []);
    }
}
