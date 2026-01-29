<?php

return [

    'quran_all_lang' => [
        'connection' => 'mysql_quran_all_lang',
        'tables' => [
            'languages' => 'languages',
            'translations' => 'translations',
            'verses' => 'quran_verses',
            'verse_texts' => 'verse_texts',
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
