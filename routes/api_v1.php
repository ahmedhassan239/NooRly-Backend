<?php

use App\Http\Controllers\Api\V1\AdhkarController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ContentScopeController;
use App\Http\Controllers\Api\V1\DailyInspirationController;
use App\Http\Controllers\Api\V1\DuaController;
use App\Http\Controllers\Api\V1\EmbedSearchController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HadithController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\LibraryHadithController;
use App\Http\Controllers\Api\V1\LibraryVersesController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\OnboardingProfileController;
use App\Http\Controllers\Api\V1\PrayerTimeController;
use App\Http\Controllers\Api\V1\QuranController;
use App\Http\Controllers\Api\V1\ReflectionController;
use App\Http\Controllers\Api\V1\Admin\NotificationCampaignController;
use App\Http\Controllers\Api\V1\NotificationDeviceTokenController;
use App\Http\Controllers\Api\V1\NotificationInboxController;
use App\Http\Controllers\Api\V1\NotificationLogController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\ContentIconController;
use App\Http\Controllers\Api\V1\RamadanGuideController;
use App\Http\Controllers\Api\V1\RamadanGuideIconController;
use App\Http\Controllers\Api\V1\HelpNowController;
use App\Http\Controllers\Api\V1\SavedItemController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserPendingNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | System & Configuration
    |--------------------------------------------------------------------------
    */
    Route::get('/content-icons/{filename}', [ContentIconController::class, 'show'])
        ->where('filename', '.+');

    Route::get('/health', [SystemController::class, 'health']);
    Route::get('/health/tables', [SystemController::class, 'tables']);
    Route::post('/events', [EventController::class, 'store']);

    // Embed search for Tiptap (hadith/ayah chips)
    Route::get('/search/hadith', [EmbedSearchController::class, 'hadith']);
    Route::get('/search/ayah', [EmbedSearchController::class, 'ayah']);

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
        Route::get('/by-category/{id}', [AdhkarController::class, 'byCategoryId'])->whereNumber('id');
        Route::get('/category/{category}', [AdhkarController::class, 'byCategory']);
        Route::get('/{id}', [AdhkarController::class, 'show']);
    });
    Route::post('/adhkar', [AdhkarController::class, 'store'])->middleware('auth:sanctum');

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
    | Ramadan Guide (Public)
    |--------------------------------------------------------------------------
    */
    Route::get('/ramadan-guide', [RamadanGuideController::class, 'index']);
    Route::get('/ramadan-guide/icons/{filename}', [RamadanGuideIconController::class, 'show'])
        ->where('filename', '.+');
    Route::get('/ramadan-guide/{slug}', [RamadanGuideController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Help Now (Public) - categorized support content
    |--------------------------------------------------------------------------
    */
    Route::get('/help-now', [HelpNowController::class, 'index']);
    Route::get('/help-now/items/{slug}', [HelpNowController::class, 'showItem']);

    /*
    |--------------------------------------------------------------------------
    | Home Dashboard (Public - for non-personalized content)
    |--------------------------------------------------------------------------
    */
    Route::get('/home/dashboard', [HomeController::class, 'dashboard']);

    /*
    |--------------------------------------------------------------------------
    | Daily Inspiration from Library Collections (Public)
    | Hadith or ayah only; IDs from HadithCollection / VerseCollection pivots.
    |--------------------------------------------------------------------------
    */
    Route::get('/daily-inspiration', DailyInspirationController::class);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/guest', [AuthController::class, 'guest']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/social/{provider}', [AuthController::class, 'social']);

        // Email OTP
        Route::post('/email/send-otp', [AuthController::class, 'sendEmailOtp']);
        Route::post('/email/verify-otp', [AuthController::class, 'verifyEmailOtp']);

        // Password reset via OTP
        Route::post('/forgot-password/request-otp', [AuthController::class, 'requestForgotPasswordOtp'])->middleware('throttle:5,1');
        Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:10,1');
        Route::post('/forgot-password/reset', [AuthController::class, 'resetForgotPassword'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    // User (Protected)
    Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/me/profile', [UserController::class, 'updateProfile']);
        Route::post('/me/profile/avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('/me', [UserController::class, 'destroyAccount']);

        // Admin campaigns: app-pull pipeline (local notifications on device; not background push)
        Route::get('/user/pending-notifications', [UserPendingNotificationController::class, 'index']);
        Route::post('/user/pending-notifications/{id}/mark-shown', [UserPendingNotificationController::class, 'markShown']);
        Route::post('/user/pending-notifications/{id}/mark-read', [UserPendingNotificationController::class, 'markRead']);

        Route::get('/me/onboarding', [OnboardingController::class, 'show']);
        Route::put('/me/onboarding', [OnboardingController::class, 'update']);

        Route::get('/me/onboarding-profile', [OnboardingProfileController::class, 'show']);
        Route::put('/me/onboarding-profile', [OnboardingProfileController::class, 'update']);

        Route::get('/me/settings', [SettingsController::class, 'show']);
        Route::put('/me/settings', [SettingsController::class, 'update']);

        // Saved reflections (lesson reflections list)
        Route::get('/reflections', [ReflectionController::class, 'index']);

        // Saved Items
        Route::get('/saved', [SavedItemController::class, 'index']);
        Route::post('/saved/{type}/{itemId}', [SavedItemController::class, 'store']);
        Route::delete('/saved/{type}/{itemId}', [SavedItemController::class, 'destroy']);

        // Daily Inspiration (user-specific, hadith/ayah/dhikr/dua; stable per user for refresh interval)
        Route::get('/home/daily-inspiration', [HomeController::class, 'dailyInspiration']);

        // Journey (weeks + lessons)
        Route::get('/journey', [JourneyController::class, 'index']);
        Route::get('/journey/summary', [JourneyController::class, 'summary']);
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

        // Notification Preferences
        Route::prefix('notifications')->group(function () {
            Route::get('/preferences', [NotificationPreferenceController::class, 'show']);
            Route::put('/preferences', [NotificationPreferenceController::class, 'update']);
            Route::post('/log/{id}/opened', [NotificationLogController::class, 'markOpened']);
            Route::get('/inbox', [NotificationInboxController::class, 'index']);
            Route::patch('/inbox/{id}/read', [NotificationInboxController::class, 'markRead']);
            Route::post('/device-token', [NotificationDeviceTokenController::class, 'store']);
        });
    });

    // Admin: manual notification campaigns (AppUser IDs in NOORLY_CAMPAIGN_ADMIN_APP_USER_IDS)
    Route::prefix('admin/notification-campaigns')->middleware(['auth:sanctum', 'campaign.admin'])->group(function () {
        Route::get('/', [NotificationCampaignController::class, 'index']);
        Route::post('/', [NotificationCampaignController::class, 'store']);
        Route::get('/{id}', [NotificationCampaignController::class, 'show']);
        Route::post('/{id}/send', [NotificationCampaignController::class, 'send']);
        Route::post('/{id}/cancel', [NotificationCampaignController::class, 'cancel']);
        Route::get('/{id}/deliveries', [NotificationCampaignController::class, 'deliveries']);
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
