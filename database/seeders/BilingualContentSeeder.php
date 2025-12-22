<?php

namespace Database\Seeders;

use App\Domain\Tasks\DailyTask;
use App\Domain\Duas\Dua;
use App\Domain\Faq\Faq;
use App\Domain\Faq\FaqCategory;
use App\Domain\Lessons\Lesson;
use Illuminate\Database\Seeder;

class BilingualContentSeeder extends Seeder
{
    public function run(): void
    {
        // Lessons (5 lessons, 3 with ar, 2 without for fallback testing)
        $lessons = [
            ['day' => 1, 'type' => 'text', 'has_ar' => true],
            ['day' => 2, 'type' => 'video', 'has_ar' => true],
            ['day' => 3, 'type' => 'text', 'has_ar' => true],
            ['day' => 4, 'type' => 'text', 'has_ar' => false], // Test fallback
            ['day' => 5, 'type' => 'video', 'has_ar' => false], // Test fallback
        ];

        foreach ($lessons as $lessonData) {
            $lesson = Lesson::create([
                'day_number' => $lessonData['day'],
                'type' => $lessonData['type'],
                'video_url' => $lessonData['type'] === 'video' ? 'https://example.com/video' . $lessonData['day'] : null,
                'duration_minutes' => 10,
            ]);

            // English translation
            $lesson->translations()->create([
                'language_code' => 'en',
                'title' => "Lesson Day {$lessonData['day']}",
                'short_description' => "Introduction to Day {$lessonData['day']} content",
                'content' => json_encode(['sections' => ["This is the content for day {$lessonData['day']}"]]),
            ]);

            // Arabic translation (conditional)
            if ($lessonData['has_ar']) {
                $lesson->translations()->create([
                    'language_code' => 'ar',
                    'title' => "درس اليوم {$lessonData['day']}",
                    'short_description' => "مقدمة لمحتوى اليوم {$lessonData['day']}",
                    'content' => json_encode(['sections' => ["هذا هو محتوى اليوم {$lessonData['day']}"]]),
                ]);
            }
        }

        // Duas (5 duas, 3 with ar)
        $duas = [
            ['has_ar' => true],
            ['has_ar' => true],
            ['has_ar' => true],
            ['has_ar' => false],
            ['has_ar' => false],
        ];

        foreach ($duas as $index => $duaData) {
            $dua = Dua::create([
                'arabic' => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
            ]);

            // English translation
            $dua->translations()->create([
                'language_code' => 'en',
                'title' => "Dua " . ($index + 1),
                'translation_text' => "In the name of Allah, the Most Gracious, the Most Merciful",
                'transliteration' => "Bismillah ir-Rahman ir-Rahim",
                'category' => 'Daily',
            ]);

            // Arabic translation (conditional)
            if ($duaData['has_ar']) {
                $dua->translations()->create([
                    'language_code' => 'ar',
                    'title' => "دعاء " . ($index + 1),
                    'translation_text' => "باسم الله الرحمن الرحيم",
                    'transliteration' => "Bismillah ir-Rahman ir-Rahim",
                    'category' => 'يومي',
                ]);
            }
        }

        // Daily Tasks (5 tasks, 4 with ar)
        $tasks = [
            ['day' => 1, 'type' => 'prayer', 'has_ar' => true],
            ['day' => 1, 'type' => 'read', 'has_ar' => true],
            ['day' => 2, 'type' => 'action', 'has_ar' => true],
            ['day' => 2, 'type' => 'sunnah', 'has_ar' => true],
            ['day' => 3, 'type' => 'prayer', 'has_ar' => false],
        ];

        foreach ($tasks as $taskData) {
            $task = DailyTask::create([
                'day_number' => $taskData['day'],
                'type' => $taskData['type'],
                'points' => 10,
            ]);

            // English translation
            $task->translations()->create([
                'language_code' => 'en',
                'title' => ucfirst($taskData['type']) . " Task",
                'description' => "Complete the {$taskData['type']} task for day {$taskData['day']}",
            ]);

            // Arabic translation (conditional)
            if ($taskData['has_ar']) {
                $task->translations()->create([
                    'language_code' => 'ar',
                    'title' => "مهمة " . $taskData['type'],
                    'description' => "أكمل مهمة {$taskData['type']} لليوم {$taskData['day']}",
                ]);
            }
        }

        // FAQ Categories (3 categories, all with en+ar)
        $categories = ['Basics', 'Prayer', 'Ramadan'];
        foreach ($categories as $index => $categoryName) {
            $category = FaqCategory::create([
                'slug' => strtolower($categoryName),
            ]);

            $category->translations()->create([
                'language_code' => 'en',
                'name' => $categoryName,
            ]);

            $category->translations()->create([
                'language_code' => 'ar',
                'name' => ['Basics' => 'الأساسيات', 'Prayer' => 'الصلاة', 'Ramadan' => 'رمضان'][$categoryName],
            ]);
        }

        // FAQs (10 faqs, 8 with en+ar, 2 only en)
        $firstCategory = FaqCategory::first();
        for ($i = 1; $i <= 10; $i++) {
            $faq = Faq::create([
                'faq_category_id' => $firstCategory->id,
            ]);

            $faq->translations()->create([
                'language_code' => 'en',
                'question' => "Question {$i}?",
                'answer' => "Answer to question {$i}.",
            ]);

            // Only first 8 have Arabic
            if ($i <= 8) {
                $faq->translations()->create([
                    'language_code' => 'ar',
                    'question' => "سؤال {$i}؟",
                    'answer' => "الإجابة على السؤال {$i}.",
                ]);
            }
        }
    }
}
