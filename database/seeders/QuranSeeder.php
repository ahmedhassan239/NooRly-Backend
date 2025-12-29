<?php

namespace Database\Seeders;

use App\Domain\Quran\QuranAyah;
use App\Domain\Quran\QuranEdition;
use App\Domain\Quran\QuranSurah;
use App\Domain\Quran\QuranTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuranSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Edition
            $edition = QuranEdition::firstOrCreate(
                ['identifier' => 'en.sahih'],
                [
                    'locale' => 'en',
                    'type' => 'translation',
                    'format' => 'text',
                    'name' => 'Saheeh International',
                    'english_name' => 'Saheeh International',
                ]
            );

            // 2. Create Surah Al-Fatiha
            $surah = QuranSurah::firstOrCreate(
                ['surah_number' => 1],
                [
                    'name_ar' => 'الفاتحة',
                    'name_en' => 'Al-Fatiha',
                    'revelation_type' => 'Meccan',
                    'ayahs_count' => 7,
                ]
            );

            // 3. Create Ayahs and Translations (Sample: First 7 Ayahs)
            $ayahs = [
                1 => ['text_ar' => 'بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 'text_en' => 'In the name of Allah, the Entirely Merciful, the Especially Merciful.'],
                2 => ['text_ar' => 'ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَٰلَمِينَ', 'text_en' => '[All] praise is [due] to Allah, Lord of the worlds -'],
                3 => ['text_ar' => 'ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 'text_en' => 'The Entirely Merciful, the Especially Merciful,'],
                4 => ['text_ar' => 'مَٰلِكِ يَوْمِ ٱلدِّينِ', 'text_en' => 'Sovereign of the Day of Recompense.'],
                5 => ['text_ar' => 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ', 'text_en' => 'It is You we worship and You we ask for help.'],
                6 => ['text_ar' => 'ٱهْدِنَا ٱلصِّرَٰطَ ٱلْمُسْتَقِيمَ', 'text_en' => 'Guide us to the straight path -'],
                7 => ['text_ar' => 'صِرَٰطَ ٱلَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ ٱلْمَغْضُوبِ عَلَيْهِمْ وَلَا ٱلضَّآلِّينَ', 'text_en' => 'The path of those upon whom You have bestowed favor, not of those who have evoked [Your] anger or of those who are astray.'],
            ];

            foreach ($ayahs as $number => $data) {
                QuranAyah::updateOrCreate(
                    ['surah_number' => 1, 'ayah_number' => $number],
                    [
                        'global_ayah_number' => $number,
                        'text_ar' => $data['text_ar'],
                    ]
                );

                QuranTranslation::updateOrCreate(
                    [
                        'global_ayah_number' => $number,
                        'locale' => 'en',
                        'edition_identifier' => $edition->identifier,
                    ],
                    [
                        'translator_name' => 'Saheeh International',
                        'text' => $data['text_en'],
                    ]
                );
            }
        });
    }
}
