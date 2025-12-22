<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyTaskController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\OnboardingController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('v1/auth')->group(function () {
    Route::post('/guest', [AuthController::class, 'guest']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'google']);
    Route::post('/facebook', [AuthController::class, 'facebook']);
    Route::post('/apple', [AuthController::class, 'apple']);
});

// Public utility routes
Route::prefix('v1')->group(function () {
    Route::get('/languages', [LanguageController::class, 'index']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/device-token', [AuthController::class, 'deviceToken']);

    // Onboarding
    Route::post('/onboarding', [OnboardingController::class, 'store']);

    // Home / Dashboard
    Route::get('/home', HomeController::class);

    // Lessons
    Route::get('/lessons', [LessonController::class, 'index']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
    Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete']);

    // Daily Tasks
    Route::get('/daily-tasks/today', [DailyTaskController::class, 'today']);
    Route::post('/daily-tasks/{dailyTask}/complete', [DailyTaskController::class, 'complete']);
});
