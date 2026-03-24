<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_upload_avatar_and_receive_avatar_url(): void
    {
        Storage::fake('public');

        $user = AppUser::factory()->create();
        AppUserProfile::create([
            'app_user_id' => $user->id,
            'name' => 'Avatar User',
            'locale' => 'en',
        ]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400)->size(256);

        $response = $this->postJson('/api/v1/me/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.avatar', fn ($value) => is_string($value) && str_starts_with($value, 'avatars/'))
            ->assertJsonPath('data.avatar_url', fn ($value) => is_string($value) && str_starts_with($value, '/storage/avatars/'));

        $avatarPath = $response->json('data.avatar');
        Storage::disk('public')->assertExists($avatarPath);
    }

    /** @test */
    public function uploading_new_avatar_replaces_old_avatar_file(): void
    {
        Storage::fake('public');

        $user = AppUser::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store("avatars/{$user->id}", 'public');
        AppUserProfile::create([
            'app_user_id' => $user->id,
            'name' => 'Avatar User',
            'locale' => 'en',
            'avatar' => $oldPath,
        ]);
        Sanctum::actingAs($user);

        $newFile = UploadedFile::fake()->image('new.png', 500, 500)->size(300);

        $response = $this->postJson('/api/v1/me/profile/avatar', [
            'avatar' => $newFile,
        ]);

        $response->assertStatus(200);

        $newPath = $response->json('data.avatar');
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    /** @test */
    public function avatar_upload_validates_type_and_size(): void
    {
        Storage::fake('public');

        $user = AppUser::factory()->create();
        AppUserProfile::create([
            'app_user_id' => $user->id,
            'name' => 'Avatar User',
            'locale' => 'en',
        ]);
        Sanctum::actingAs($user);

        $invalidType = UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf');
        $tooLarge = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB

        $this->postJson('/api/v1/me/profile/avatar', [
            'avatar' => $invalidType,
        ])->assertStatus(422)->assertJsonPath('status', false);

        $this->postJson('/api/v1/me/profile/avatar', [
            'avatar' => $tooLarge,
        ])->assertStatus(422)->assertJsonPath('status', false);
    }
}

