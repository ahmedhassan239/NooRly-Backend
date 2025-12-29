<?php

namespace Database\Seeders;

use App\Domain\Hadith\HadithCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HadithSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Collection (Sahih al-Bukhari)
            $collection = HadithCollection::firstOrCreate(
                ['collection_key' => 'bukhari'],
                [
                    'name_en' => 'Sahih al-Bukhari',
                    'name_ar' => 'صحيح البخاري',
                ]
            );

            // 2. Create Hadith Item (First Hadith)
            \App\Domain\Hadith\HadithItem::firstOrCreate(
                [
                    'collection_key' => $collection->collection_key,
                    'book_number' => 1,
                    'hadith_number' => 1,
                ],
                [
                    'text_en' => 'I heard Allah\'s Messenger (ﷺ) saying, "The reward of deeds depends upon the intentions and every person will get the reward according to what he has intended. So whoever emigrated for worldly benefits or for a woman to marry, his emigration was for what he emigrated for."',
                    'text_ar' => 'سَمِعْتُ رَسُولَ اللَّهِ صلى الله عليه وسلم يَقُولُ " إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ، وَإِنَّمَا لِكُلِّ امْرِئٍ مَا نَوَى، فَمَنْ كَانَتْ هِجْرَتُهُ إِلَى دُنْيَا يُصِيبُهَا أَوْ إِلَى امْرَأَةٍ يَنْكِحُهَا، فَهِجْرَتُهُ إِلَى مَا هَاجَرَ إِلَيْهِ "',
                    'grade' => 'Sahih',
                    'reference' => 'Reference: 1',
                ]
            );
        });
    }
}
