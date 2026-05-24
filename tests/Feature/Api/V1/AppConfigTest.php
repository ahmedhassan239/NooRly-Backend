<?php

namespace Tests\Feature\Api\V1;

use App\Domain\AppSettings\AppSetting;
use App\Domain\Home\HomeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test settings
        AppSetting::create([
            'key' => 'app_name',
            'value' => 'ق Test',
            'group' => 'general',
            'type' => 'string',
            'is_public' => true,
        ]);

        AppSetting::create([
            'key' => 'maintenance_mode',
            'value' => false,
            'group' => 'general',
            'type' => 'boolean',
            'is_public' => true,
        ]);

        AppSetting::create([
            'key' => 'secret_key',
            'value' => 'secret123',
            'group' => 'internal',
            'type' => 'string',
            'is_public' => false,
        ]);

        // Create test home sections
        HomeSection::create([
            'key' => 'daily_verse',
            'title' => ['en' => 'Verse of the Day', 'ar' => 'آية اليوم'],
            'type' => 'single',
            'source_type' => 'verses',
            'position' => 1,
            'is_active' => true,
        ]);

        HomeSection::create([
            'key' => 'inactive_section',
            'title' => ['en' => 'Inactive', 'ar' => 'غير نشط'],
            'type' => 'list',
            'position' => 2,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_returns_app_config_with_public_settings_only()
    {
        $response = $this->getJson('/api/v1/app-config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'settings',
                    'home_sections',
                    'locale',
                    'server_time',
                ],
            ])
            ->assertJson([
                'status' => true,
            ]);

        // Should include public settings
        $this->assertArrayHasKey('app_name', $response->json('data.settings'));
        $this->assertArrayHasKey('maintenance_mode', $response->json('data.settings'));
        
        // Should NOT include private settings
        $this->assertArrayNotHasKey('secret_key', $response->json('data.settings'));
    }

    /** @test */
    public function it_returns_only_active_home_sections()
    {
        $response = $this->getJson('/api/v1/app-config');

        $response->assertStatus(200);

        $sections = collect($response->json('data.home_sections'));
        
        // Should include active section
        $this->assertTrue($sections->contains('key', 'daily_verse'));
        
        // Should NOT include inactive section
        $this->assertFalse($sections->contains('key', 'inactive_section'));
    }

    /** @test */
    public function it_respects_accept_language_header()
    {
        $response = $this->getJson('/api/v1/app-config', [
            'Accept-Language' => 'ar',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'locale' => 'ar',
                ],
            ]);
    }

    /** @test */
    public function it_returns_specific_public_setting()
    {
        $response = $this->getJson('/api/v1/app-config/settings/app_name');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'key' => 'app_name',
                    'value' => 'ق Test',
                    'type' => 'string',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_private_setting()
    {
        $response = $this->getJson('/api/v1/app-config/settings/secret_key');

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_setting()
    {
        $response = $this->getJson('/api/v1/app-config/settings/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_home_sections_endpoint()
    {
        $response = $this->getJson('/api/v1/app-config/home-sections');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'key',
                        'title',
                        'type',
                        'position',
                    ],
                ],
            ]);
    }
}
