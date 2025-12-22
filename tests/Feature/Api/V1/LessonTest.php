<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_lessons()
    {
        Lesson::factory()->count(5)->create();

        $user = AppUser::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/lessons');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_show_lesson_details()
    {
        $lesson = Lesson::factory()->create();
        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/lessons/{$lesson->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $lesson->id);
    }

    public function test_complete_lesson()
    {
        $lesson = Lesson::factory()->create();
        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lessons/{$lesson->id}/complete");

        $response->assertOk();

        // Verify DB relationship
        $this->assertTrue($user->completedLessons()->where('lesson_id', $lesson->id)->exists());
    }
}
