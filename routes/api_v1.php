<?php

use App\Http\Controllers\Api\V1\AdhkarController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ContentScopeController;
use App\Http\Controllers\Api\V1\DuaController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\HadithController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LibraryHadithController;
use App\Http\Controllers\Api\V1\LibraryVersesController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\PrayerTimeController;
use App\Http\Controllers\Api\V1\QuranController;
use App\Http\Controllers\Api\V1\SavedItemController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | System & Configuration
    |--------------------------------------------------------------------------
    */
    Route::get('/health', [SystemController::class, 'health']);
    Route::get('/health/tables', [SystemController::class, 'tables']);
    Route::post('/events', [EventController::class, 'store']);
    
    // App Configuration (Remote Config)
    Route::get('/app-config', [AppConfigController::class, 'index']);
    Route::get('/app-config/settings/{key}', [AppConfigController::class, 'show']);
    Route::get('/app-config/home-sections', [AppConfigController::class, 'homeSections']);

    /*
    |--------------------------------------------------------------------------
    | Prayer Times
    |--------------------------------------------------------------------------
    */
    Route::get('/prayer-times', [PrayerTimeController::class, 'index']);
    Route::get('/calendar/hijri', [PrayerTimeController::class, 'calendar']);

    /*
    |--------------------------------------------------------------------------
    | Quran (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('quran')->group(function () {
        Route::get('/surahs', [QuranController::class, 'surahs']);
        Route::get('/surahs/{surah}', [QuranController::class, 'surah']);
        Route::get('/verses/{id}', [QuranController::class, 'verse']);
        Route::get('/verses/{surah}/{ayah}', [QuranController::class, 'verseByReference']);
        Route::get('/languages', [QuranController::class, 'languages']);
        Route::get('/search', [QuranController::class, 'search']);
        Route::get('/daily', [QuranController::class, 'daily']);
    });

    /*
    |--------------------------------------------------------------------------
    | Hadith (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('hadith')->group(function () {
        Route::get('/', [HadithController::class, 'index']);
        Route::get('/collections', [HadithController::class, 'collections']);
        Route::get('/collections/{collection}', [HadithController::class, 'collection']);
        Route::get('/search', [HadithController::class, 'search']);
        Route::get('/daily', [HadithController::class, 'daily']);
        Route::get('/{id}', [HadithController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Duas (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('duas')->group(function () {
        Route::get('/', [DuaController::class, 'index']);
        Route::get('/categories', [DuaController::class, 'categories']);
        Route::get('/category/{category}', [DuaController::class, 'byCategory']);
        Route::get('/search', [DuaController::class, 'search']);
        Route::get('/{id}', [DuaController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Adhkar (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('adhkar')->group(function () {
        Route::get('/', [AdhkarController::class, 'index']);
        Route::get('/categories', [AdhkarController::class, 'categories']);
        Route::get('/category/{category}', [AdhkarController::class, 'byCategory']);
        Route::get('/{id}', [AdhkarController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Content Scopes (Public) - Library tabs etc.
    |--------------------------------------------------------------------------
    */
    Route::get('/content-scopes', [ContentScopeController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Categories (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Library Hadith (categories → collections → hadiths)
    |--------------------------------------------------------------------------
    */
    Route::prefix('library/hadith')->group(function () {
        Route::get('/categories', [LibraryHadithController::class, 'categories']);
        Route::get('/categories/{id}/collections', [LibraryHadithController::class, 'collectionsByCategory']);
        Route::get('/collections', [LibraryHadithController::class, 'collections']);
        Route::get('/collections/{id}', [LibraryHadithController::class, 'collection']);
    });

    /*
    |--------------------------------------------------------------------------
    | Library Verses (categories → collections → verses)
    |--------------------------------------------------------------------------
    */
    Route::prefix('library/verses')->group(function () {
        Route::get('/categories', [LibraryVersesController::class, 'categories']);
        Route::get('/categories/{id}/collections', [LibraryVersesController::class, 'collectionsByCategory']);
        Route::get('/collections', [LibraryVersesController::class, 'collections']);
        Route::get('/collections/{id}', [LibraryVersesController::class, 'collection']);
    });

    /*
    |--------------------------------------------------------------------------
    | Home Dashboard (Public - for non-personalized content)
    |--------------------------------------------------------------------------
    */
    Route::get('/home/dashboard', [HomeController::class, 'dashboard']);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/guest', [AuthController::class, 'guest']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/social/{provider}', [AuthController::class, 'social']);
        
        // Email OTP
        Route::post('/email/send-otp', [AuthController::class, 'sendEmailOtp']);
        Route::post('/email/verify-otp', [AuthController::class, 'verifyEmailOtp']);
        
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    // User (Protected)
    Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {
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

        // Journey (weeks + lessons)
        Route::get('/journey', [JourneyController::class, 'index']);
        Route::get('/journey/weeks/{week}', [JourneyController::class, 'week']);

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
