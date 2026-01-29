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

    // External Content (Quran & Hadith)
    Route::prefix('quran')->group(function () {
        Route::get('/search', [\App\Http\Controllers\Api\ExternalContentController::class, 'searchQuran']);
    });
    Route::prefix('hadith')->group(function () {
        Route::get('/search', [\App\Http\Controllers\Api\ExternalContentController::class, 'searchHadith']);
    });

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

    // Admin / Management (Quran All Languages)
    Route::prefix('admin/quran-all-lang')->middleware('auth:sanctum')->group(function () {
        // Languages
        Route::get('/languages', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'indexLanguages']);
        Route::post('/languages', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'storeLanguage']);
        Route::get('/languages/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'showLanguage']);
        Route::put('/languages/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'updateLanguage']);
        Route::delete('/languages/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'destroyLanguage']);

        // Translations
        Route::get('/translations', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'indexTranslations']);
        Route::post('/translations', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'storeTranslation']);
        Route::get('/translations/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'showTranslation']);
        Route::put('/translations/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'updateTranslation']);
        Route::delete('/translations/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'destroyTranslation']);

        // Verses
        Route::get('/verses', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'indexVerses']);
        Route::get('/verses/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'showVerse']);
        
        // Verse Texts
        Route::put('/verse-texts/{id}', [\App\Http\Controllers\Api\V1\Admin\QuranAllLangController::class, 'updateVerseText']);
    });

});
