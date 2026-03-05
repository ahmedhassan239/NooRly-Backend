<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Lessons\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobilePatchTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and token
        $this->user = \Database\Factories\AppUserFactory::new()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Mock lesson JSONs
        Storage::fake('local');
        $lessonsData = [
            [
                'id' => 'lesson_1',
                'day_number' => 1,
                'week_number' => 1,
                'title' => 'Test Lesson 1',
                'summary' => 'Summary 1',
                'content' => 'Content 1',
                'estimated_minutes' => 5,
            ],
            [
                'id' => 'lesson_2',
                'day_number' => 2,
                'week_number' => 1,
                'title' => 'Test Lesson 2',
                'summary' => 'Summary 2',
                'content' => 'Content 2',
                'estimated_minutes' => 10,
            ],
        ];

        Storage::put('content/lessons/en.json', json_encode($lessonsData));
        Storage::put('content/lessons/ar.json', json_encode($lessonsData));
    }

    /** @test */
    public function it_can_get_onboarding_and_settings()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/me/onboarding');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['start_date', 'timezone']]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/me/settings');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['language', 'dark_mode']]);
    }

    /** @test */
    public function it_can_update_settings()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/v1/me/settings', [
                'language' => 'ar',
                'dark_mode' => true,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('ar', $this->user->fresh()->settings->language);
        $this->assertTrue($this->user->fresh()->settings->dark_mode);
    }

    /** @test */
    public function it_can_get_today_lesson()
    {
        $week = JourneyWeek::create([
            'week_number' => 1,
            'title' => 'Week 1',
            'is_active' => true,
        ]);
        $lesson = Lesson::factory()->create(['title' => 'Test Lesson 1', 'duration_minutes' => 5]);
        JourneyWeekLesson::create([
            'journey_week_id' => $week->id,
            'lesson_id' => $lesson->id,
            'day_number' => 1,
            'position' => 1,
            'sort_order' => 101,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/lessons/today');

        $response->assertStatus(200)
            ->assertJsonPath('data.lesson_id', $lesson->id)
            ->assertJsonPath('data.day', 1)
            ->assertJsonPath('data.week', 1)
            ->assertJsonPath('data.status', 'current');
    }

    /** @test */
    public function it_can_complete_lesson_and_save_reflection()
    {
        $lessonId = 'lesson_1';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/lessons/{$lessonId}/complete");

        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_completions', [
            'app_user_id' => $this->user->id,
            'lesson_id' => $lessonId,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/v1/lessons/{$lessonId}/reflection", [
                'reflection_text' => 'Great lesson',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_reflections', [
            'app_user_id' => $this->user->id,
            'lesson_id' => $lessonId,
            'reflection_text' => 'Great lesson',
        ]);
    }

    /** @test */
    public function it_can_save_and_remove_items()
    {
        $lessonId = 'lesson_1';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/saved/lesson/{$lessonId}");

        $response->assertStatus(201);
        $this->assertDatabaseHas('saved_items', [
            'app_user_id' => $this->user->id,
            'item_type' => 'lesson',
            'item_id' => $lessonId,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/v1/saved/lesson/{$lessonId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('saved_items', [
            'app_user_id' => $this->user->id,
            'item_id' => $lessonId,
        ]);
    }

    /** @test */
    public function it_can_get_prayer_times_with_caching()
    {
        Http::fake([
            'api.aladhan.com/*' => Http::response([
                'data' => [
                    'timings' => ['Fajr' => '05:00'],
                    'date' => ['readable' => '23 Dec 2025'],
                    'meta' => ['method' => ['name' => 'ISNA'], 'timezone' => 'UTC'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/prayer-times?lat=30&lng=31');

        $response->assertStatus(200)
            ->assertJsonPath('data.timings.Fajr', '05:00');

        // Second call should come from cache (no Http call)
        Http::assertSentCount(1);
        $response = $this->getJson('/api/v1/prayer-times?lat=30&lng=31');
        $response->assertStatus(200);
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_logs_events()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/events', [
                'event_type' => 'test_event',
                'entity_type' => 'test_entity',
                'entity_id' => '123',
                'meta' => ['foo' => 'bar'],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('app_events', [
            'app_user_id' => $this->user->id,
            'event_type' => 'test_event',
        ]);
    }
}
