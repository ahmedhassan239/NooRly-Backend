<?php

return [
    'quran' => [
        'connection' => 'mysql_quran',
        'table' => 'quran.ayahs',
        'columns' => [
            'surah_number' => 'surah_id',
            'ayah_number' => 'number',
            'text_ar' => 'text',
        ],
    ],
    'hadith' => [
        'connection' => 'mysql_hadith',
        'table' => 'all_hadiths_clean.hadiths',
        'columns' => [
            'collection' => 'source',
            'book_number' => 'chapter_no',
            'hadith_number' => 'hadith_no',
            'text_ar' => 'text_ar',
            'text_en' => 'text_en',
        ],
    ],
];
