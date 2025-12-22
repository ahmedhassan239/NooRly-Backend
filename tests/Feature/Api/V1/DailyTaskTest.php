<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Tasks\DailyTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_today_tasks()
    {
        // Tasks depend on 'day_number' logic, usually current_day
        // We'll create tasks for day 1
        DailyTask::factory()->count(3)->create(['day_number' => 1]);
        
        $user = AppUser::factory()->create(['current_day' => 1]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/daily-tasks/today');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_complete_task()
    {
        $task = DailyTask::factory()->create();
        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/daily-tasks/{$task->id}/complete");

        $response->assertOk();

        $this->assertTrue($user->completedTasks()->where('daily_task_id', $task->id)->exists());
    }
}
