<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Journey\JourneyWeekTranslation;
use App\Domain\Lessons\Lesson;
use App\Domain\Lessons\LessonCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/lessons/today — uses Journey progress (current = first non-completed).
 * Does not use LessonDatasetService. Never returns 500.
 */
class LessonTodayLocaleTest extends TestCase
{
    use RefreshDatabase;

    /** Current lesson exists in journey -> 200 with new format. */
    public function test_today_returns_200_with_current_lesson_from_journey(): void
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
        $lesson = Lesson::factory()->create([
            'title' => 'First Lesson',
            'duration_minutes' => 8,
        ]);
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $lesson->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/lessons/today', ['Accept-Language' => 'en']);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', "Today's lesson retrieved")
            ->assertJsonPath('data.lesson_id', $lesson->id)
            ->assertJsonPath('data.title', 'First Lesson')
            ->assertJsonPath('data.day', 1)
            ->assertJsonPath('data.week', 1)
            ->assertJsonPath('data.estimated_read_time', 8)
            ->assertJsonPath('data.status', 'current');
    }

    /** All lessons completed -> 200 with data null and "No lesson today". */
    public function test_today_returns_200_with_null_data_when_all_lessons_completed(): void
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

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/lessons/today');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'No lesson today')
            ->assertJsonPath('data', null);
    }

    /** No journey / no lessons -> 200 with data null (never 500). */
    public function test_today_returns_200_with_null_data_when_no_journey_lessons(): void
    {
        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/lessons/today');

        $response->assertStatus(200);
        $this->assertNotEquals(500, $response->status());
        $response->assertJsonPath('data', null);
    }
}
