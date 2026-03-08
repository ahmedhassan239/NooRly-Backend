<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Duas JSON import path
    |--------------------------------------------------------------------------
    | Absolute path or path relative to project root (e.g. storage/app/duas.json).
    | Can be overridden by --file= on the command.
    */
    'json_path' => env('DUAS_IMPORT_JSON_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Target scope ID for categories.id = 3
    |--------------------------------------------------------------------------
    | Value to set on categories.scope_id for the category record with id = 3.
    | Must be a valid content_scope id. Overridden by --target-scope-id=.
    */
    'target_scope_id' => env('DUAS_IMPORT_TARGET_SCOPE_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Database connections
    |--------------------------------------------------------------------------
    | Connection names for duas table and categories table.
    | If both tables are in the same database, use the same connection.
    */
    'duas_connection' => env('DUAS_IMPORT_DUAS_CONNECTION', config('database.default')),
    'categories_connection' => env('DUAS_IMPORT_CATEGORIES_CONNECTION', config('database.default')),

];
