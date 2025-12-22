<?php

namespace Database\Seeders;

use App\Domain\Duas\Dua;
use App\Domain\Faq\Faq;
use App\Domain\Faq\FaqCategory;
use App\Domain\Lessons\Lesson;
use App\Domain\Tasks\DailyTask;
use Illuminate\Database\Seeder;

class IslamicContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLessons();
        $this->seedDailyTasks();
        $this->seedDuas();
        $this->seedFaqs();
    }

    private function seedLessons()
    {
        $lessons = [
            [
                'day_number' => 1,
                'title' => 'Welcome & The Shahada',
                'content' => [
                    'blocks' => [
                        ['type' => 'text', 'content' => 'Welcome to your new journey! Today is the start of something beautiful.'],
                        ['type' => 'header', 'content' => 'The Meaning of Shahada'],
                        ['type' => 'text', 'content' => 'The Shahada is the declaration of faith: "There is no god but Allah, and Muhammad is His Messenger."'],
                    ],
                ],
                'type' => 'text',
                'duration_minutes' => 5,
            ],
            [
                'day_number' => 2,
                'title' => 'Who is Allah?',
                'content' => [
                    'blocks' => [
                        ['type' => 'text', 'content' => 'Allah is the Creator, the Sustainer, and the Merciful.'],
                    ],
                ],
                'type' => 'text',
                'duration_minutes' => 7,
            ],
            [
                'day_number' => 3,
                'title' => 'Introduction to Wudu (Ablution)',
                'content' => [
                    'blocks' => [
                        ['type' => 'text', 'content' => 'Before we pray, we must purify ourselves. This is called Wudu.'],
                        ['type' => 'video', 'url' => 'https://www.youtube.com/watch?v=example'],
                    ],
                ],
                'type' => 'video',
                'duration_minutes' => 10,
            ],
        ];

        foreach ($lessons as $lesson) {
            Lesson::firstOrCreate(['day_number' => $lesson['day_number']], $lesson);
        }
    }

    private function seedDailyTasks()
    {
        $tasks = [
            // Day 1
            ['day_number' => 1, 'title' => 'Say the Shahada with conviction', 'type' => 'action', 'points' => 10],
            ['day_number' => 1, 'title' => 'Listen to Surah Al-Fatiha', 'type' => 'listen', 'points' => 10],
            ['day_number' => 1, 'title' => 'Make a sincere Dua for guidance', 'type' => 'prayer', 'points' => 10],

            // Day 2
            ['day_number' => 2, 'title' => 'Say "SubhanAllah" 33 times', 'type' => 'dhikr', 'points' => 5],
            ['day_number' => 2, 'title' => 'Reflect on 3 blessings', 'type' => 'action', 'points' => 10],

            // Day 3
            ['day_number' => 3, 'title' => 'Practice the steps of Wudu', 'type' => 'action', 'points' => 20],
        ];

        foreach ($tasks as $task) {
            DailyTask::firstOrCreate([
                'day_number' => $task['day_number'],
                'title' => $task['title'],
            ], $task);
        }
    }

    private function seedDuas()
    {
        $duas = [
            [
                'title' => 'Before Sleeping',
                'arabic' => 'بِاسْمِكَ اللَّهُمَّ أَمُوتُ وَأَحْيَا',
                'translation' => 'In Your Name, O Allah, I die and I live.',
                'transliteration' => 'Bismika Allahumma amootu wa ahya.',
                'category' => 'Daily',
            ],
            [
                'title' => 'Before Eating',
                'arabic' => 'بِسْمِ اللَّهِ',
                'translation' => 'In the name of Allah.',
                'transliteration' => 'Bismillah.',
                'category' => 'Daily',
            ],
            [
                'title' => 'For Forgiveness',
                'arabic' => 'أَسْتَغْفِرُ اللَّهَ وَأَتُوبُ إِلَيْهِ',
                'translation' => 'I seek forgiveness from Allah and repent to Him.',
                'transliteration' => 'Astaghfirullah wa atoobu ilayh.',
                'category' => 'Spiritual',
            ],
        ];

        foreach ($duas as $dua) {
            Dua::firstOrCreate(['title' => $dua['title']], $dua);
        }
    }

    private function seedFaqs()
    {
        $worship = FaqCategory::firstOrCreate(['slug' => 'worship'], ['name' => 'Worship & Prayer']);
        $lifestyle = FaqCategory::firstOrCreate(['slug' => 'lifestyle'], ['name' => 'Lifestyle']);

        $faqs = [
            [
                'faq_category_id' => $worship->id,
                'question' => 'Do I have to pray in Arabic?',
                'answer' => 'Ideally, the core parts of the prayer (Salah) should be in Arabic, but you can learn gradually. Supplications (Dua) can be in any language.',
            ],
            [
                'faq_category_id' => $lifestyle->id,
                'question' => 'Is seafood Halal?',
                'answer' => 'Yes, generally speaking, most seafood is considered Halal in Islam.',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq);
        }
    }
}
