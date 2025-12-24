<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SavedItemController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\PrayerTimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // System
    Route::get('/health', [SystemController::class, 'health']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/prayer-times', [PrayerTimeController::class, 'index']);
    Route::get('/calendar/hijri', [PrayerTimeController::class, 'calendar']);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/guest', [AuthController::class, 'guest']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/social/{provider}', [AuthController::class, 'social']);
        
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    // User (Protected)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/me/profile', [UserController::class, 'updateProfile']);
        
        Route::get('/me/onboarding', [OnboardingController::class, 'show']);
        Route::put('/me/onboarding', [OnboardingController::class, 'update']);
        
        Route::get('/me/settings', [SettingsController::class, 'show']);
        Route::put('/me/settings', [SettingsController::class, 'update']);

        // Saved Items
        Route::get('/saved', [SavedItemController::class, 'index']);
        Route::post('/saved/{type}/{itemId}', [SavedItemController::class, 'store']);
        Route::delete('/saved/{type}/{itemId}', [SavedItemController::class, 'destroy']);

        // Lessons
        Route::prefix('lessons')->group(function () {
            Route::get('/', [LessonController::class, 'index']);
            Route::get('/today', [LessonController::class, 'today']);
            Route::get('/progress', [LessonController::class, 'progress']);
            Route::get('/{id}', [LessonController::class, 'show']);
            Route::post('/{id}/complete', [LessonController::class, 'complete']);
            Route::put('/{id}/reflection', [LessonController::class, 'reflection']);
        });
    });

});
