<?php

namespace Tests\Feature;

use App\Models\Domain\Language\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class I18nLanguageResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed languages
        Language::create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
        ]);
        
        Language::create([
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    public function test_query_param_has_highest_priority()
    {
        $response = $this->getJson('/api/v1/languages?lang=ar');
        
        $this->assertEquals('ar', request()->attributes->get('lang'));
    }

    public function test_accept_language_header_resolved()
    {
        $response = $this->getJson('/api/v1/languages', [
            'Accept-Language' => 'ar-SA,ar;q=0.9,en;q=0.8'
        ]);
        
        $this->assertEquals('ar', request()->attributes->get('lang'));
    }

    public function test_x_lang_header_resolved()
    {
        $response = $this->getJson('/api/v1/languages', [
            'X-Lang' => 'ar'
        ]);
        
        $this->assertEquals('ar', request()->attributes->get('lang'));
    }

    public function test_query_param_overrides_headers()
    {
        $response = $this->getJson('/api/v1/languages?lang=en', [
            'Accept-Language' => 'ar',
            'X-Lang' => 'ar',
        ]);
        
        $this->assertEquals('en', request()->attributes->get('lang'));
    }

    public function test_defaults_to_en_when_no_lang_specified()
    {
        $response = $this->getJson('/api/v1/languages');
        
        $this->assertEquals('en', request()->attributes->get('lang'));
    }

    public function test_invalid_language_falls_back_to_default()
    {
        $response = $this->getJson('/api/v1/languages?lang=invalid');
        
        $this->assertEquals('en', request()->attributes->get('lang'));
        $this->assertEquals('en', request()->attributes->get('fallback_lang'));
    }

    public function test_inactive_language_falls_back_to_default()
    {
        Language::create([
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'direction' => 'ltr',
            'is_active' => false,
            'is_default' => false,
        ]);

        $response = $this->getJson('/api/v1/languages?lang=fr');
        
        $this->assertEquals('en', request()->attributes->get('lang'));
    }

    public function test_languages_endpoint_returns_active_languages()
    {
        $response = $this->getJson('/api/v1/languages');
        
        $response->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonFragment(['code' => 'en', 'is_default' => true])
                ->assertJsonFragment(['code' => 'ar', 'direction' => 'rtl']);
    }
}
