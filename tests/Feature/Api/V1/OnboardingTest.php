<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use App\Domain\Auth\AppUserProfile;
use App\Domain\Users\AppUserOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AppUser::factory()->create();
        AppUserProvider::create([
            'app_user_id' => $this->user->id,
            'provider' => 'email',
            'email' => 'onboarding@example.com',
            'password' => bcrypt('secret'),
        ]);
        AppUserProfile::create([
            'app_user_id' => $this->user->id,
            'name' => 'Onboarding User',
            'locale' => 'en',
        ]);
    }

    /** @test */
    public function onboarding_show_returns_full_state_and_creates_record_if_missing()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/me/onboarding');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_step', AppUserOnboarding::STEP_SHAHADA_DATE)
            ->assertJsonPath('data.completed', false)
            ->assertJsonStructure([
                'data' => [
                    'shahada_date',
                    'goals',
                    'summary_completed',
                    'current_step',
                    'completed',
                    'completed_at',
                    'start_date',
                    'timezone',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('app_user_onboarding', ['app_user_id' => $this->user->id]);
    }

    /** @test */
    public function onboarding_update_persists_and_show_returns_same()
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/me/onboarding', [
            'shahada_date' => '2026-02-01',
        ])->assertStatus(200);

        $show = $this->getJson('/api/v1/me/onboarding');
        $show->assertStatus(200)
            ->assertJsonPath('data.shahada_date', '2026-02-01')
            ->assertJsonPath('data.current_step', AppUserOnboarding::STEP_GOALS);

        $this->putJson('/api/v1/me/onboarding', [
            'goals' => ['Read Quran daily', 'Pray on time'],
        ])->assertStatus(200);

        $show2 = $this->getJson('/api/v1/me/onboarding');
        $show2->assertStatus(200)
            ->assertJsonPath('data.shahada_date', '2026-02-01')
            ->assertJsonPath('data.goals', ['Read Quran daily', 'Pray on time'])
            ->assertJsonPath('data.current_step', AppUserOnboarding::STEP_SUMMARY);
    }

    /** @test */
    public function completing_summary_sets_completed_at_and_step_done()
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/me/onboarding', [
            'shahada_date' => '2026-02-01',
            'goals' => ['Goal 1'],
        ])->assertStatus(200);

        $this->putJson('/api/v1/me/onboarding', [
            'summary_completed' => true,
        ])->assertStatus(200)
            ->assertJsonPath('data.completed', true)
            ->assertJsonPath('data.current_step', AppUserOnboarding::STEP_DONE)
            ->assertJsonPath('data.completed_at', fn ($v) => $v !== null);

        $onboarding = AppUserOnboarding::where('app_user_id', $this->user->id)->first();
        $this->assertNotNull($onboarding->completed_at);
        $this->assertTrue($onboarding->summary_completed);
        $this->assertEquals(AppUserOnboarding::STEP_DONE, $onboarding->current_step);
    }

    /** @test */
    public function onboarding_update_accepts_partial_payloads()
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/me/onboarding', ['goals' => ['A', 'B']])
            ->assertStatus(200)
            ->assertJsonPath('data.goals', ['A', 'B']);

        $this->putJson('/api/v1/me/onboarding', ['shahada_date' => '2025-01-15'])->assertStatus(200);

        $response = $this->getJson('/api/v1/me/onboarding');
        $response->assertJsonPath('data.shahada_date', '2025-01-15')
            ->assertJsonPath('data.goals', ['A', 'B']);
    }

    /** @test */
    public function onboarding_requires_authentication()
    {
        $this->getJson('/api/v1/me/onboarding')->assertStatus(401);
        $this->putJson('/api/v1/me/onboarding', ['shahada_date' => '2026-02-01'])->assertStatus(401);
    }
}
