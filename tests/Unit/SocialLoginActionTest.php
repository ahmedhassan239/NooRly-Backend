<?php

namespace Tests\Unit;

use App\Application\Auth\SocialLoginAction;
use App\Domain\Auth\AppUser;
use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\Enums\Provider;
use App\Domain\Auth\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Mocks\FakeSocialAuthProvider;
use Tests\TestCase;

class SocialLoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_user_if_not_exists()
    {
        $provider = new FakeSocialAuthProvider('123', 'new@example.com', 'New User');
        $action = new SocialLoginAction($provider);

        $user = $action->execute(null, 'valid-token', Provider::Google);

        $this->assertEquals('new@example.com', $user->email);
        $this->assertDatabaseHas('app_users', ['email' => 'new@example.com']);
        $this->assertDatabaseHas('social_accounts', ['provider_user_id' => '123']);
    }

    public function test_finds_existing_user_by_email()
    {
        $existingUser = AppUser::factory()->create(['email' => 'existing@example.com']);
        
        $provider = new FakeSocialAuthProvider('456', 'existing@example.com', 'Existing User');
        $action = new SocialLoginAction($provider);

        $user = $action->execute(null, 'valid-token', Provider::Google);

        $this->assertEquals($existingUser->id, $user->id);
        // Should have created a social account link
        $this->assertDatabaseHas('social_accounts', [
            'app_user_id' => $existingUser->id,
            'provider_user_id' => '456',
        ]);
    }

    public function test_upgrades_guest_user()
    {
        $guest = AppUser::factory()->guest()->create();
        
        $provider = new FakeSocialAuthProvider('789', 'guest_upgrade@example.com', 'Upgraded User');
        $action = new SocialLoginAction($provider);

        $user = $action->execute($guest, 'valid-token', Provider::Google);

        $this->assertEquals($guest->id, $user->id);
        $this->assertFalse($user->fresh()->is_guest);
        $this->assertEquals('guest_upgrade@example.com', $user->fresh()->email);
    }
}
