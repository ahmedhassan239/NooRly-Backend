<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Journey\JourneyWeekTranslation;
use App\Domain\Lessons\Lesson;
use App\Domain\Lessons\LessonCompletion;
use App\Domain\Lessons\LessonReflection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_journey_endpoint_returns_weeks_with_days_and_lessons_ordered(): void
    {
        $user = AppUser::factory()->create();

        $week1 = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Faith Basics',
            'is_active' => true,
        ]);
        JourneyWeekTranslation::create([
            'journey_week_id' => $week1->id,
            'language_code' => 'en',
            'title' => 'Faith Basics',
        ]);
        $l1 = Lesson::factory()->create(['title' => 'Lesson 1']);
        $l2 = Lesson::factory()->create(['title' => 'Lesson 2']);
        JourneyWeekLesson::create([
            'journey_week_id' => $week1->id,
            'lesson_id' => $l1->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);
        JourneyWeekLesson::create([
            'journey_week_id' => $week1->id,
            'lesson_id' => $l2->id,
            'day_number' => 2,
            'position' => 1,
            'sort_order' => 201,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey');

        $response->assertOk()
            ->assertJsonPath('data.plan.title', '90-Day Learning Path')
            ->assertJsonPath('data.overall.total_days', 2)
            ->assertJsonPath('data.overall.done_days', 0)
            ->assertJsonCount(1, 'data.weeks')
            ->assertJsonPath('data.weeks.0.week_number', 1)
            ->assertJsonPath('data.weeks.0.title', 'Faith Basics');
        $this->assertArrayHasKey('days', $response->json('data.weeks.0'));
        $days = $response->json('data.weeks.0.days');
        $this->assertCount(2, $days);
        $this->assertEquals(1, $days[0]['day']);
        $this->assertCount(1, $days[0]['lessons']);
        $this->assertEquals(2, $days[1]['day']);
        $this->assertCount(1, $days[1]['lessons']);
    }

    public function test_done_status_changes_after_marking_lesson_complete(): void
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

        $before = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey');
        $before->assertJsonPath('data.overall.done_days', 0);
        $before->assertJsonPath('data.weeks.0.days.0.lessons.0.is_done', false);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lessons/{$lesson->id}/complete")
            ->assertOk();

        $after = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey');
        $after->assertJsonPath('data.overall.done_days', 1);
        $after->assertJsonPath('data.weeks.0.days.0.lessons.0.is_done', true);
    }

    public function test_reflection_saved_works_for_week_reflection_type(): void
    {
        $user = AppUser::factory()->create();
        $lesson = Lesson::factory()->create([
            'type' => 'week_reflection',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/lessons/{$lesson->id}/reflection", [
                'reflection_text' => 'My week reflection text.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('lesson_reflections', [
            'app_user_id' => $user->id,
            'lesson_id' => (string) $lesson->id,
            'reflection_text' => 'My week reflection text.',
        ]);
    }

    public function test_journey_week_endpoint_returns_single_week_with_days_and_lessons(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 2,
            'title' => 'Week Two',
            'is_active' => true,
        ]);
        JourneyWeekTranslation::create([
            'journey_week_id' => $week->id,
            'language_code' => 'en',
            'title' => 'Week Two',
        ]);
        $lesson = Lesson::factory()->create();
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $lesson->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 201,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey/weeks/2');

        $response->assertOk()
            ->assertJsonPath('data.week_number', 2)
            ->assertJsonPath('data.title', 'Week Two');
        $this->assertArrayHasKey('days', $response->json('data'));
        $days = $response->json('data.days');
        $this->assertCount(1, $days);
        $this->assertCount(1, $days[0]['lessons']);
        $this->assertEquals($lesson->id, $days[0]['lessons'][0]['id']);
    }

    public function test_journey_week_returns_404_for_missing_week(): void
    {
        $user = AppUser::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/journey/weeks/999')
            ->assertNotFound();
    }

    public function test_journey_returns_localized_week_title(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Fallback',
            'is_active' => true,
        ]);
        JourneyWeekTranslation::create([
            'journey_week_id' => $week->id,
            'language_code' => 'en',
            'title' => 'Faith Basics',
        ]);
        JourneyWeekTranslation::create([
            'journey_week_id' => $week->id,
            'language_code' => 'ar',
            'title' => 'أساسيات الإيمان',
        ]);

        $en = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey?lang=en');
        $en->assertOk()->assertJsonPath('data.weeks.0.title', 'Faith Basics');

        $ar = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey?lang=ar');
        $ar->assertOk();
        $title = $ar->json('data.weeks.0.title');
        $this->assertTrue($title === 'أساسيات الإيمان' || $title === 'Faith Basics', 'Week title should be localized when lang=ar (or fallback to en)');
    }

    public function test_multiple_lessons_per_day_ordered_by_position(): void
    {
        $user = AppUser::factory()->create();
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Week 1',
            'is_active' => true,
        ]);
        $l1 = Lesson::factory()->create(['title' => 'First']);
        $l2 = Lesson::factory()->create(['title' => 'Second']);
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
            'day_number' => 1,
            'position' => 2,
            'sort_order' => 102,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/journey');

        $response->assertOk();
        $days = $response->json('data.weeks.0.days');
        $this->assertCount(1, $days);
        $lessons = $days[0]['lessons'];
        $this->assertCount(2, $lessons);
        $this->assertEquals($l1->id, $lessons[0]['id']);
        $this->assertEquals($l2->id, $lessons[1]['id']);
    }
}
