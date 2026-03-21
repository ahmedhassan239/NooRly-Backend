<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Push notifications (FCM / future providers)
    |--------------------------------------------------------------------------
    | driver: "null" (honest no-op) or "fcm" (stub until HTTP send is implemented)
    */
    'push' => [
        'driver' => env('NOORLY_PUSH_DRIVER', 'null'),
        'fcm' => [
            'server_key' => env('FCM_SERVER_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign admin API (Sanctum / AppUser)
    |--------------------------------------------------------------------------
    | If empty, /api/v1/admin/notification-campaigns* returns 403 for all app users.
    | Set to app user IDs allowed to call the campaign API (e.g. internal testers).
    */
    'campaign_admin_app_user_ids' => array_filter(array_map('intval', explode(',', (string) env('NOORLY_CAMPAIGN_ADMIN_APP_USER_IDS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Audience defaults
    |--------------------------------------------------------------------------
    */
    'audience' => [
        'active_last_days' => (int) env('NOORLY_ACTIVE_USER_DAYS', 14),
        'inactive_after_days' => (int) env('NOORLY_INACTIVE_USER_DAYS', 30),
    ],
];
