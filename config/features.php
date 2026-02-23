<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    |
    | Used by Content Scopes: if a scope has feature_flag set (e.g. "adhkar"),
    | it is only included in the API when the corresponding key below is true.
    | Allows enabling sections per environment or app version.
    |
    */
    'adhkar' => env('FEATURE_ADHKAR', true),
    'hadith' => env('FEATURE_HADITH', true),
    'verses' => env('FEATURE_VERSES', true),
];
