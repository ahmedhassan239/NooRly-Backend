# NooRly Backend API Audit Report

**Date:** February 3, 2026  
**Purpose:** Comprehensive audit of backend API endpoints vs Flutter app requirements

---

## Executive Summary

The NooRly backend has **42 existing API endpoints**, but the Flutter app currently uses **local JSON assets** instead of the backend API. This report identifies gaps between what the backend provides and what the Flutter app needs for full dynamic content management.

### Key Findings

| Category | Status |
|----------|--------|
| Auth endpoints | ✅ Complete |
| User/Profile endpoints | ✅ Complete |
| Onboarding endpoints | ✅ Complete |
| Settings endpoints | ✅ Complete |
| Lessons endpoints | ✅ Complete |
| Prayer times | ⚠️ Partial (stub Hijri) |
| Duas endpoints | ❌ Missing |
| Adhkar endpoints | ❌ Missing |
| Quran public endpoints | ⚠️ Partial (search only) |
| Hadith public endpoints | ⚠️ Partial (search only) |
| Categories endpoints | ❌ Missing (routes exist but not wired) |
| Home/Dashboard endpoints | ❌ Missing |
| App Config/Remote Config | ❌ Missing |
| Saved Items | ⚠️ Partial (missing verse/adhkar types) |

---

## 1. Flutter App Feature Matrix

### Current Flutter Data Sources

| Feature | Current Source | Backend API Needed |
|---------|---------------|-------------------|
| Duas list/detail | `assets/data/duas.json` | `GET /duas`, `GET /duas/{id}` |
| Hadith list/detail | `assets/data/hadith.json` | `GET /hadith`, `GET /hadith/{id}` |
| Verses list/detail | `assets/data/verses.json` | `GET /quran/verses`, `GET /quran/verses/{id}` |
| Adhkar list/detail | `assets/data/adhkar.json` | `GET /adhkar`, `GET /adhkar/{id}` |
| Lessons | `assets/data/lessons.json` | ✅ `GET /lessons/*` |
| Prayer times | `PrayerMockData` | ✅ `GET /prayer-times` |
| Profile | `ProfileMockData` | ✅ `GET /me` |
| Saved items | `SharedPreferences` | ⚠️ `GET /saved` (missing types) |
| Home dashboard | `HomeMockData` | ❌ `GET /home/dashboard` |
| App config | Hardcoded | ❌ `GET /app-config` |

### Required API Endpoints for Flutter

#### Auth (✅ Complete)
- [x] `POST /auth/guest` - Guest authentication
- [x] `POST /auth/register` - Email registration
- [x] `POST /auth/login` - Email login
- [x] `POST /auth/social/{provider}` - Social login (google, facebook, apple)
- [x] `POST /auth/logout` - Logout
- [ ] `POST /auth/forgot-password` - Password reset request
- [ ] `POST /auth/reset-password` - Password reset

#### User (✅ Complete)
- [x] `GET /me` - Current user
- [x] `PUT /me/profile` - Update profile
- [x] `GET /me/onboarding` - Get onboarding data
- [x] `PUT /me/onboarding` - Update onboarding
- [x] `GET /me/settings` - Get settings
- [x] `PUT /me/settings` - Update settings

#### Lessons (✅ Complete)
- [x] `GET /lessons` - List lessons
- [x] `GET /lessons/today` - Today's lesson
- [x] `GET /lessons/progress` - User progress
- [x] `GET /lessons/{id}` - Lesson detail
- [x] `POST /lessons/{id}/complete` - Mark complete
- [x] `PUT /lessons/{id}/reflection` - Save reflection

#### Duas (❌ Missing)
- [ ] `GET /duas` - List duas (paginated, filterable by category)
- [ ] `GET /duas/{id}` - Dua detail
- [ ] `GET /duas/categories` - List dua categories
- [ ] `GET /duas/search` - Search duas

#### Adhkar (❌ Missing)
- [ ] `GET /adhkar` - List adhkar (paginated, filterable by category)
- [ ] `GET /adhkar/{id}` - Dhikr detail
- [ ] `GET /adhkar/categories` - List adhkar categories

#### Quran (⚠️ Partial)
- [ ] `GET /quran/surahs` - List all surahs
- [ ] `GET /quran/surahs/{id}` - Surah with verses
- [ ] `GET /quran/verses/{id}` - Single verse detail
- [x] `GET /quran/search` - Search verses

#### Hadith (⚠️ Partial)
- [ ] `GET /hadith/collections` - List collections/books
- [ ] `GET /hadith/collections/{id}` - Collection with hadiths
- [ ] `GET /hadith/{id}` - Single hadith detail
- [x] `GET /hadith/search` - Search hadiths

#### Categories (❌ Missing - routes exist but not in api_v1.php)
- [ ] `GET /categories` - List categories (with scope filter)
- [ ] `GET /categories/{id}` - Category detail with attached content

#### Saved Items (⚠️ Partial)
- [x] `GET /saved` - List saved items
- [x] `POST /saved/{type}/{id}` - Save item
- [x] `DELETE /saved/{type}/{id}` - Unsave item
- **Issue:** Only supports `dua`, `hadith`, `lesson` types. Flutter uses `verse` and `adhkar` too.

#### Home/Dashboard (❌ Missing)
- [ ] `GET /home/dashboard` - Aggregated home data (ayah of day, hadith of day, streak, etc.)

#### App Config (❌ Missing)
- [ ] `GET /app-config` - Remote configuration for app

#### Daily Tasks (❌ Missing public API)
- [ ] `GET /daily-tasks` - List daily tasks
- [ ] `GET /daily-tasks/{id}` - Task detail
- [ ] `POST /daily-tasks/{id}/complete` - Mark complete

---

## 2. Existing Backend Inventory

### Route Files
| File | Purpose |
|------|---------|
| `routes/api.php` | Main entry, includes `api_v1.php` |
| `routes/api_v1.php` | All v1 API routes |

### Existing Endpoints (42 total)

#### System (2 endpoints)
| Method | Path | Controller | Status |
|--------|------|------------|--------|
| GET | `/v1/health` | `SystemController@health` | ✅ |
| POST | `/v1/events` | `EventController@store` | ✅ |

#### Prayer Times (2 endpoints)
| Method | Path | Controller | Status |
|--------|------|------------|--------|
| GET | `/v1/prayer-times` | `PrayerTimeController@index` | ✅ |
| GET | `/v1/calendar/hijri` | `PrayerTimeController@calendar` | ⚠️ Stub |

#### External Content (2 endpoints)
| Method | Path | Controller | Status |
|--------|------|------------|--------|
| GET | `/v1/quran/search` | `ExternalContentController@searchQuran` | ✅ |
| GET | `/v1/hadith/search` | `ExternalContentController@searchHadith` | ✅ |

#### Auth (5 endpoints)
| Method | Path | Controller | Auth | Status |
|--------|------|------------|------|--------|
| POST | `/v1/auth/guest` | `AuthController@guest` | No | ✅ |
| POST | `/v1/auth/register` | `AuthController@register` | No | ✅ |
| POST | `/v1/auth/login` | `AuthController@login` | No | ✅ |
| POST | `/v1/auth/social/{provider}` | `AuthController@social` | No | ✅ |
| POST | `/v1/auth/logout` | `AuthController@logout` | Yes | ✅ |

#### User (Protected) (9 endpoints)
| Method | Path | Controller | Status |
|--------|------|------------|--------|
| GET | `/v1/me` | `UserController@me` | ✅ |
| PUT | `/v1/me/profile` | `UserController@updateProfile` | ✅ |
| GET | `/v1/me/onboarding` | `OnboardingController@show` | ✅ |
| PUT | `/v1/me/onboarding` | `OnboardingController@update` | ✅ |
| GET | `/v1/me/settings` | `SettingsController@show` | ✅ |
| PUT | `/v1/me/settings` | `SettingsController@update` | ✅ |
| GET | `/v1/saved` | `SavedItemController@index` | ✅ |
| POST | `/v1/saved/{type}/{id}` | `SavedItemController@store` | ✅ |
| DELETE | `/v1/saved/{type}/{id}` | `SavedItemController@destroy` | ✅ |

#### Lessons (Protected) (6 endpoints)
| Method | Path | Controller | Status |
|--------|------|------------|--------|
| GET | `/v1/lessons` | `LessonController@index` | ✅ |
| GET | `/v1/lessons/today` | `LessonController@today` | ✅ |
| GET | `/v1/lessons/progress` | `LessonController@progress` | ✅ |
| GET | `/v1/lessons/{id}` | `LessonController@show` | ✅ |
| POST | `/v1/lessons/{id}/complete` | `LessonController@complete` | ✅ |
| PUT | `/v1/lessons/{id}/reflection` | `LessonController@reflection` | ✅ |

#### Admin Quran All Lang (Protected) (16 endpoints)
All under `/v1/admin/quran-all-lang/*` - for admin management only.

---

## 3. Known Bugs

### Bug #1: Quran Language Priority (Bengali vs English)

**Symptom:** Verse details show Arabic + Bengali instead of Arabic + English.

**Root Cause:** 
1. Preview fallback in `QuranAllLangVerseResource.php` uses `orderBy('languages.code')` which is alphabetical (`bn` before `en`).
2. `showVerse` API endpoint has no explicit ordering.

**Location:**
- `app/Filament/Resources/QuranAllLangVerseResource.php` lines 108, 142
- `app/Http/Controllers/Api/V1/Admin/QuranAllLangController.php` lines 274-296

**Fix Required:**
1. Replace `orderBy('languages.code')` with `orderByLanguagePriority()` scope
2. Add ordering to `showVerse` API endpoint
3. Verify English language is active in database

---

## 4. Missing Filament Resources

The following content types need Filament resources for dashboard management:

| Content Type | Filament Resource | Status |
|--------------|------------------|--------|
| Lessons | `LessonResource` | ✅ Exists |
| Duas | `DuaResource` | ✅ Exists |
| Daily Tasks | `DailyTaskResource` | ✅ Exists |
| Categories | `CategoryResource` | ✅ Exists |
| Content Scopes | `ContentScopeResource` | ✅ Exists |
| Hadith Items | `HadithItemResource` | ✅ Exists |
| Quran Verses | `QuranAllLangVerseResource` | ✅ Exists |
| **App Settings** | - | ❌ Missing |
| **Remote Config** | - | ❌ Missing |
| **Home Sections** | - | ❌ Missing |
| **Adhkar** | - | ❌ Missing |

---

## 5. Implementation Plan

### Phase 1: Critical Fixes
1. Fix Quran language priority bug
2. Add missing saved item types (verse, adhkar)
3. Wire existing CategoryController to routes

### Phase 2: Content APIs
1. Create Duas API endpoints
2. Create Adhkar API endpoints (need model + migration)
3. Create Daily Tasks public API
4. Expand Quran API (surahs, verses)
5. Expand Hadith API (collections, items)

### Phase 3: App Configuration
1. Create AppSettings model + migration
2. Create RemoteConfig model + migration
3. Create HomeSections model + migration
4. Create Filament resources for all
5. Create `/app-config` API endpoint

### Phase 4: Home Dashboard
1. Create HomeController with aggregated data
2. Implement "Ayah of the Day" logic
3. Implement "Hadith of the Day" logic
4. Implement streak/progress aggregation

### Phase 5: Arabic Search
1. Add `text_normalized` columns where missing
2. Create backfill artisan command
3. Add model observers for auto-normalization
4. Update search endpoints to use normalized columns

### Phase 6: Testing
1. Auth endpoint tests
2. Language activation tests
3. Category attachment tests
4. Favorites save/unsave tests
5. Search tests (with/without tashkeel)
6. Remote config schema tests

---

## 6. Database Changes Required

### New Tables
```sql
-- App Settings / Remote Config
CREATE TABLE app_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value JSON,
    group VARCHAR(100) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Home Sections Configuration
CREATE TABLE home_sections (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(100) UNIQUE NOT NULL,
    title JSON, -- translatable
    type ENUM('featured', 'list', 'carousel', 'banner'),
    source_type VARCHAR(100), -- lessons, duas, hadith, etc.
    query_config JSON, -- filters, limits, etc.
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    locale VARCHAR(10) DEFAULT NULL, -- null = all locales
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Adhkar (if not exists)
CREATE TABLE adhkar (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT UNSIGNED,
    title JSON, -- translatable
    text JSON, -- translatable
    text_normalized VARCHAR(1000), -- for search
    count INT DEFAULT 1,
    reward TEXT,
    source VARCHAR(255),
    audio_url VARCHAR(500),
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Column Additions
```sql
-- Add normalized text for Arabic search (if not exists)
ALTER TABLE hadith_items ADD COLUMN text_ar_normalized TEXT;
ALTER TABLE duas ADD COLUMN text_ar_normalized TEXT;
```

---

## 7. Environment Variables

Ensure these are set in `.env`:

```env
# Hadith Database (env-driven, no hardcoding)
DB_HADITH_CONNECTION=mysql_hadith
HADITH_TABLE_QUALIFIED=hadiths

# Quran All Lang Database
DB_QURAN_ALL_LANG_CONNECTION=mysql_quran_all_lang
```

---

## 8. Response Format Standard

All API endpoints must return:

```json
{
    "status": true,
    "message": "Success",
    "data": { ... },
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

Error responses:
```json
{
    "status": false,
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

---

## 9. Controllers to Create/Update

| Controller | Status | Actions Needed |
|------------|--------|----------------|
| `DuaController` | ❌ Create | index, show, search, categories |
| `AdhkarController` | ❌ Create | index, show, categories |
| `DailyTaskController` | ⚠️ Exists | Add public API methods |
| `HomeController` | ⚠️ Exists | Add dashboard method |
| `AppConfigController` | ❌ Create | index (public config) |
| `QuranController` | ❌ Create | surahs, verses, detail |
| `HadithController` | ❌ Create | collections, items, detail |
| `CategoryController` | ⚠️ Exists | Wire to routes |

---

## 10. Files Changed/Created Summary

### To Create
- `app/Http/Controllers/Api/V1/DuaController.php`
- `app/Http/Controllers/Api/V1/AdhkarController.php`
- `app/Http/Controllers/Api/V1/AppConfigController.php`
- `app/Http/Controllers/Api/V1/QuranController.php`
- `app/Http/Controllers/Api/V1/HadithController.php`
- `app/Http/Resources/Api/V1/DuaResource.php`
- `app/Http/Resources/Api/V1/AdhkarResource.php`
- `app/Http/Resources/Api/V1/QuranVerseResource.php`
- `app/Http/Resources/Api/V1/HadithResource.php`
- `app/Http/Resources/Api/V1/CategoryResource.php`
- `app/Domain/Adhkar/Adhkar.php`
- `app/Domain/AppSettings/AppSetting.php`
- `app/Domain/Home/HomeSection.php`
- `app/Filament/Resources/AppSettingResource.php`
- `app/Filament/Resources/HomeSectionResource.php`
- `app/Filament/Resources/AdhkarResource.php`
- `database/migrations/xxxx_create_app_settings_table.php`
- `database/migrations/xxxx_create_home_sections_table.php`
- `database/migrations/xxxx_create_adhkar_table.php`
- `database/migrations/xxxx_add_normalized_text_columns.php`
- `app/Console/Commands/BackfillNormalizedText.php`
- `tests/Feature/Api/DuaApiTest.php`
- `tests/Feature/Api/AdhkarApiTest.php`
- `tests/Feature/Api/QuranApiTest.php`
- `tests/Feature/Api/HadithApiTest.php`
- `tests/Feature/Api/AppConfigApiTest.php`
- `tests/Feature/Api/LanguageActivationTest.php`

### To Update
- `routes/api_v1.php` - Add all new routes
- `app/Http/Controllers/Api/V1/SavedItemController.php` - Add verse/adhkar types
- `app/Filament/Resources/QuranAllLangVerseResource.php` - Fix language priority
- `app/Http/Controllers/Api/V1/Admin/QuranAllLangController.php` - Fix ordering
- `docs/API_SPEC.md` - Update with all endpoints

---

**Report Generated:** February 3, 2026
