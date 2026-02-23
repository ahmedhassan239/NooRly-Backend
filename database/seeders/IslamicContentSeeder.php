<?php

namespace Database\Seeders;

use App\Domain\Duas\Dua;
use App\Domain\Faq\Faq;
use App\Domain\Faq\FaqCategory;
use App\Domain\Lessons\Lesson;
use Illuminate\Database\Seeder;

class IslamicContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLessons();
        // Daily tasks are seeded by DailyTaskSeeder (with translations)
        $this->seedDuas();
        $this->seedFaqs();
    }

    private function seedLessons()
    {
        $lessons = [
            [
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
            Lesson::firstOrCreate(['title' => $lesson['title']], $lesson);
        }
    }

    private function seedDuas()
    {
        $duas = [
            [
                'dua_key' => 'before-sleeping',
                'category_key' => 'daily',
                'text_ar' => 'بِاسْمِكَ اللَّهُمَّ أَمُوتُ وَأَحْيَا',
                'text_en' => 'In Your Name, O Allah, I die and I live.',
                'transliteration' => 'Bismika Allahumma amootu wa ahya.',
            ],
            [
                'dua_key' => 'before-eating',
                'category_key' => 'daily',
                'text_ar' => 'بِسْمِ اللَّهِ',
                'text_en' => 'In the name of Allah.',
                'transliteration' => 'Bismillah.',
            ],
            [
                'dua_key' => 'for-forgiveness',
                'category_key' => 'spiritual',
                'text_ar' => 'أَسْتَغْفِرُ اللَّهَ وَأَتُوبُ إِلَيْهِ',
                'text_en' => 'I seek forgiveness from Allah and repent to Him.',
                'transliteration' => 'Astaghfirullah wa atoobu ilayh.',
            ],
        ];

        foreach ($duas as $dua) {
            Dua::firstOrCreate(['dua_key' => $dua['dua_key']], $dua);
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
