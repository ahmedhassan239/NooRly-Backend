<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\Lesson;
use App\Domain\Tasks\DailyTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_requires_authentication()
    {
        $response = $this->getJson('/api/v1/home');
        $response->assertUnauthorized();
    }

    public function test_home_returns_correct_structure()
    {
        $user = AppUser::factory()->create();
        
        // Seed some data so home isn't empty
        Lesson::factory()->count(3)->create();
        DailyTask::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'daily_tasks', 
                    'lesson',
                ]
            ]);
    }
}
