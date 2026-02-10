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
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('title'); // translatable: {"en": "Daily Verse", "ar": "آية اليوم"}
            $table->json('subtitle')->nullable(); // translatable
            $table->string('type', 50)->default('list'); // featured, list, carousel, banner, single
            $table->string('source_type', 100)->nullable(); // lessons, duas, hadith, verses, adhkar, etc.
            $table->json('query_config')->nullable(); // filters, limits, sorting, etc.
            $table->string('icon', 100)->nullable(); // icon name
            $table->string('route', 255)->nullable(); // deep link route in app
            $table->integer('position')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('locale', 10)->nullable()->index(); // null = all locales
            $table->timestamps();
        });

        // Seed default home sections
        $this->seedDefaultSections();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }

    /**
     * Seed default home sections
     */
    private function seedDefaultSections(): void
    {
        $sections = [
            [
                'key' => 'daily_verse',
                'title' => json_encode(['en' => 'Verse of the Day', 'ar' => 'آية اليوم']),
                'subtitle' => json_encode(['en' => 'Reflect on today\'s verse', 'ar' => 'تأمل في آية اليوم']),
                'type' => 'single',
                'source_type' => 'verses',
                'query_config' => json_encode(['daily' => true, 'limit' => 1]),
                'icon' => 'book-open',
                'route' => '/quran/daily',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'daily_hadith',
                'title' => json_encode(['en' => 'Hadith of the Day', 'ar' => 'حديث اليوم']),
                'subtitle' => json_encode(['en' => 'Learn from the Prophet\'s teachings', 'ar' => 'تعلم من أحاديث النبي']),
                'type' => 'single',
                'source_type' => 'hadith',
                'query_config' => json_encode(['daily' => true, 'limit' => 1]),
                'icon' => 'academic-cap',
                'route' => '/hadith/daily',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'journey_progress',
                'title' => json_encode(['en' => 'Your Journey', 'ar' => 'رحلتك']),
                'subtitle' => json_encode(['en' => 'Continue your learning path', 'ar' => 'أكمل مسيرة التعلم']),
                'type' => 'single',
                'source_type' => 'lessons',
                'query_config' => json_encode(['current' => true]),
                'icon' => 'map',
                'route' => '/journey',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'featured_duas',
                'title' => json_encode(['en' => 'Featured Duas', 'ar' => 'أدعية مميزة']),
                'subtitle' => json_encode(['en' => 'Supplications for every occasion', 'ar' => 'أدعية لكل مناسبة']),
                'type' => 'carousel',
                'source_type' => 'duas',
                'query_config' => json_encode(['featured' => true, 'limit' => 5]),
                'icon' => 'hand-raised',
                'route' => '/duas',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'key' => 'daily_tasks',
                'title' => json_encode(['en' => 'Daily Tasks', 'ar' => 'المهام اليومية']),
                'subtitle' => json_encode(['en' => 'Build good habits', 'ar' => 'ابنِ عادات حسنة']),
                'type' => 'list',
                'source_type' => 'daily_tasks',
                'query_config' => json_encode(['active' => true, 'limit' => 5]),
                'icon' => 'check-circle',
                'route' => '/tasks',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'key' => 'morning_adhkar',
                'title' => json_encode(['en' => 'Morning Adhkar', 'ar' => 'أذكار الصباح']),
                'subtitle' => json_encode(['en' => 'Start your day with remembrance', 'ar' => 'ابدأ يومك بالذكر']),
                'type' => 'single',
                'source_type' => 'adhkar',
                'query_config' => json_encode(['category' => 'morning', 'time_based' => true]),
                'icon' => 'sun',
                'route' => '/adhkar/morning',
                'position' => 6,
                'is_active' => true,
            ],
            [
                'key' => 'evening_adhkar',
                'title' => json_encode(['en' => 'Evening Adhkar', 'ar' => 'أذكار المساء']),
                'subtitle' => json_encode(['en' => 'End your day with remembrance', 'ar' => 'اختم يومك بالذكر']),
                'type' => 'single',
                'source_type' => 'adhkar',
                'query_config' => json_encode(['category' => 'evening', 'time_based' => true]),
                'icon' => 'moon',
                'route' => '/adhkar/evening',
                'position' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($sections as $section) {
            \DB::table('home_sections')->insert(array_merge($section, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
