<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Administration
    |--------------------------------------------------------------------------
    |
    | Configuration for managing sensitive religious content.
    |
    */

    // Allow deletion of external content (Quran/Hadith).
    // Set to false to disable delete actions in the admin panel.
    'admin_allow_delete' => env('CONTENT_ADMIN_ALLOW_DELETE', false),
];
