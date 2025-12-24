<?php

// API v1 Routes
require __DIR__ . '/api_v1.php';

// Legacy / Other routes (Keeping for reference or if still needed)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ...
});
