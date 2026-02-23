<?php

namespace Database\Seeders;

use App\Domain\Tasks\DailyTask;
use Illuminate\Database\Seeder;

class DailyTaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = $this->getSeedTasks();

        foreach ($tasks as $taskData) {
            $task = DailyTask::firstOrCreate(
                [
                    'day_number' => $taskData['day_number'],
                    'type' => $taskData['type'],
                ],
                [
                    'points' => $taskData['points'],
                ]
            );

            $task->translations()->updateOrCreate(
                ['language_code' => 'en'],
                [
                    'title' => $taskData['en_title'],
                    'description' => $taskData['en_description'],
                ]
            );

            if (isset($taskData['ar_title'], $taskData['ar_description'])) {
                $task->translations()->updateOrCreate(
                    ['language_code' => 'ar'],
                    [
                        'title' => $taskData['ar_title'],
                        'description' => $taskData['ar_description'],
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array{day_number: int, type: string, points: int, en_title: string, en_description: string, ar_title?: string, ar_description?: string}>
     */
    private function getSeedTasks(): array
    {
        return [
            [
                'day_number' => 1,
                'type' => 'action',
                'points' => 10,
                'en_title' => 'Say the Shahada with conviction',
                'en_description' => '<p>Declare the testimony of faith with sincerity and understanding. The Shahada is: <strong>There is no god but Allah, and Muhammad is His Messenger.</strong></p><p>Reflect on its meaning and let it strengthen your commitment to the journey ahead.</p>',
                'ar_title' => 'قل الشهادة بقناعة',
                'ar_description' => '<p>قل الشهادة بإخلاص وفهم. الشهادة هي: <strong>أشهد أن لا إله إلا الله وأشهد أن محمداً رسول الله.</strong></p><p>تأمل في معناها واجعلها تعزز التزامك بالرحلة المقبلة.</p>',
            ],
            [
                'day_number' => 1,
                'type' => 'read',
                'points' => 10,
                'en_title' => 'Listen to Surah Al-Fatiha',
                'en_description' => '<p>Listen to the opening chapter of the Quran, Surah Al-Fatiha. It is recited in every unit of prayer.</p><ul><li>Find a recitation (audio or video)</li><li>Listen at least once with focus</li><li>Reflect on the meaning of "The Opener"</li></ul>',
                'ar_title' => 'استمع إلى سورة الفاتحة',
                'ar_description' => '<p>استمع إلى السورة الافتتاحية للقرآن، سورة الفاتحة. تُتلى في كل ركعة من الصلاة.</p><ul><li>ابحث عن تلاوة (صوتية أو فيديو)</li><li>استمع مرة واحدة على الأقل بتركيز</li><li>تأمل في معنى "الفاتحة"</li></ul>',
            ],
            [
                'day_number' => 1,
                'type' => 'prayer',
                'points' => 10,
                'en_title' => 'Make a sincere Dua for guidance',
                'en_description' => '<p>Turn to Allah and ask for guidance (hidayah) in your new journey. Speak from the heart in your own words or use a known supplication.</p><blockquote><p>O Allah, guide me and make me steadfast.</p></blockquote>',
                'ar_title' => 'ادعُ دعاءً مخلصاً للهداية',
                'ar_description' => '<p>التفت إلى الله واطلب الهداية في رحلتك الجديدة. تكلم من القلب بكلماتك أو استخدم دعاءً معروفاً.</p><blockquote><p>اللهم اهدني وثبتني.</p></blockquote>',
            ],
            [
                'day_number' => 2,
                'type' => 'sunnah',
                'points' => 5,
                'en_title' => 'Say "SubhanAllah" 33 times',
                'en_description' => '<p>Practice this simple dhikr (remembrance): <strong>SubhanAllah</strong> (Glory be to Allah).</p><p>Say it 33 times with presence of heart. You can use fingers or a tasbih to count.</p>',
                'ar_title' => 'قل "سبحان الله" 33 مرة',
                'ar_description' => '<p>مارس هذا الذكر البسيط: <strong>سبحان الله</strong>.</p><p>قلها 33 مرة بحضور القلب. يمكنك استخدام الأصابع أو المسبحة للعد.</p>',
            ],
            [
                'day_number' => 2,
                'type' => 'action',
                'points' => 10,
                'en_title' => 'Reflect on 3 blessings',
                'en_description' => '<p>Take a moment to recognise three blessings in your life. Write them down or say them out loud.</p><p>Gratitude (shukr) is a core part of faith and well-being.</p>',
                'ar_title' => 'تأمل في 3 نعم',
                'ar_description' => '<p>خذ لحظة للتعرف على ثلاث نعم في حياتك. اكتبها أو قلها بصوت عالٍ.</p><p>الشكر جزء أساسي من الإيمان والرفاهية.</p>',
            ],
            [
                'day_number' => 3,
                'type' => 'action',
                'points' => 20,
                'en_title' => 'Practice the steps of Wudu',
                'en_description' => '<p>Learn or practise the steps of ablution (wudu) before prayer.</p><ol><li>Intention (niyyah)</li><li>Wash hands, mouth, nose, face</li><li>Arms to elbows</li><li>Wipe head and ears</li><li>Feet to ankles</li></ol><p>Use a reliable guide or video if needed.</p>',
                'ar_title' => 'تمرن على خطوات الوضوء',
                'ar_description' => '<p>تعلم أو مارس خطوات الوضوء قبل الصلاة.</p><ol><li>النية</li><li>غسل اليدين والفم والأنف والوجه</li><li>الذراعين إلى المرفقين</li><li>مسح الرأس والأذنين</li><li>القدمين إلى الكعبين</li></ol><p>استخدم دليلاً أو فيديو موثوقاً إذا لزم الأمر.</p>',
            ],
            [
                'day_number' => 4,
                'type' => 'read',
                'points' => 10,
                'en_title' => 'Read a short verse and its meaning',
                'en_description' => '<p>Choose one short verse (ayah) from the Quran. Read it in Arabic if you can, and then read a translation or tafsir of its meaning.</p>',
                'ar_title' => 'اقرأ آية قصيرة ومعناها',
                'ar_description' => '<p>اختر آية قصيرة من القرآن. اقرأها بالعربية إن استطعت، ثم اقرأ ترجمة أو تفسيراً لمعناها.</p>',
            ],
            [
                'day_number' => 5,
                'type' => 'prayer',
                'points' => 15,
                'en_title' => 'Make Dua for someone else',
                'en_description' => '<p>Make a sincere supplication for another person—family, friend, or someone in need. Praying for others is a beloved act and increases barakah.</p>',
            ],
        ];
    }
}
