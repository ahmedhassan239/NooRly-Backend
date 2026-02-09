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
    'connection' => env('DB_HADITH_CONNECTION', 'mysql_hadith'),

    // ✅ خليها من الـ env عشان مايبقاش فيه اختلاف بين local و production
    // القيمة الافتراضية تفضل القديمة عشان اللي عنده local على all_hadiths_clean يشتغل
    'table' => env('HADITH_TABLE_QUALIFIED', 'all_hadiths_clean.hadiths'),

    'columns' => [
        'collection' => 'source',
        'book_number' => 'chapter_no',
        'hadith_number' => 'hadith_no',
        'text_ar' => 'text_ar',
        'text_en' => 'text_en',
    ],
],

];
