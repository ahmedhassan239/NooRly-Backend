<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->unique();
            $table->json('value')->nullable();
            $table->string('group', 100)->default('general')->index();
            $table->string('type', 50)->default('string'); // string, boolean, integer, json, array
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false)->index(); // exposed via API
            $table->timestamps();
        });

        // Seed default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }

    /**
     * Seed default app settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // General
            [
                'key' => 'app_name',
                'value' => json_encode('NooRly'),
                'group' => 'general',
                'type' => 'string',
                'description' => 'Application name',
                'is_public' => true,
            ],
            [
                'key' => 'app_version',
                'value' => json_encode('1.0.0'),
                'group' => 'general',
                'type' => 'string',
                'description' => 'Current app version',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => json_encode(false),
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_message',
                'value' => json_encode(['en' => 'App is under maintenance', 'ar' => 'التطبيق تحت الصيانة']),
                'group' => 'general',
                'type' => 'json',
                'description' => 'Maintenance mode message (translatable)',
                'is_public' => true,
            ],

            // Features
            [
                'key' => 'features_enabled',
                'value' => json_encode(['lessons', 'duas', 'hadith', 'quran', 'adhkar', 'prayer_times']),
                'group' => 'features',
                'type' => 'array',
                'description' => 'List of enabled features/modules',
                'is_public' => true,
            ],
            [
                'key' => 'default_locale',
                'value' => json_encode('en'),
                'group' => 'features',
                'type' => 'string',
                'description' => 'Default app locale',
                'is_public' => true,
            ],
            [
                'key' => 'supported_locales',
                'value' => json_encode(['en', 'ar']),
                'group' => 'features',
                'type' => 'array',
                'description' => 'Supported locales',
                'is_public' => true,
            ],

            // Home
            [
                'key' => 'home_sections_order',
                'value' => json_encode(['daily_verse', 'daily_hadith', 'journey_progress', 'featured_duas', 'daily_tasks']),
                'group' => 'home',
                'type' => 'array',
                'description' => 'Order of sections on home screen',
                'is_public' => true,
            ],
            [
                'key' => 'show_daily_verse',
                'value' => json_encode(true),
                'group' => 'home',
                'type' => 'boolean',
                'description' => 'Show daily verse on home',
                'is_public' => true,
            ],
            [
                'key' => 'show_daily_hadith',
                'value' => json_encode(true),
                'group' => 'home',
                'type' => 'boolean',
                'description' => 'Show daily hadith on home',
                'is_public' => true,
            ],
            [
                'key' => 'show_journey_progress',
                'value' => json_encode(true),
                'group' => 'home',
                'type' => 'boolean',
                'description' => 'Show journey progress on home',
                'is_public' => true,
            ],

            // Content
            [
                'key' => 'daily_content_refresh_hour',
                'value' => json_encode(0),
                'group' => 'content',
                'type' => 'integer',
                'description' => 'Hour (0-23) when daily content refreshes',
                'is_public' => false,
            ],
            [
                'key' => 'featured_duas_count',
                'value' => json_encode(5),
                'group' => 'content',
                'type' => 'integer',
                'description' => 'Number of featured duas to show',
                'is_public' => true,
            ],
            [
                'key' => 'featured_hadith_count',
                'value' => json_encode(5),
                'group' => 'content',
                'type' => 'integer',
                'description' => 'Number of featured hadith to show',
                'is_public' => true,
            ],

            // Prayer Times
            [
                'key' => 'default_prayer_method',
                'value' => json_encode(2), // ISNA
                'group' => 'prayer',
                'type' => 'integer',
                'description' => 'Default prayer calculation method',
                'is_public' => true,
            ],
            [
                'key' => 'default_madhab',
                'value' => json_encode(0), // Shafi
                'group' => 'prayer',
                'type' => 'integer',
                'description' => 'Default madhab for Asr calculation',
                'is_public' => true,
            ],

            // Notifications
            [
                'key' => 'notifications_enabled',
                'value' => json_encode(true),
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Global notifications toggle',
                'is_public' => true,
            ],
            [
                'key' => 'prayer_notifications_default',
                'value' => json_encode(true),
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Default prayer notifications setting',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('app_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
