# ق API Specification (v1)

**Version:** 1.0.0  
**Base URL:** `/api/v1`  
**Last Updated:** February 10, 2026

---

## Overview

The ق API provides endpoints for the Flutter mobile app to access Islamic content including Quran, Hadith, Duas, Adhkar, and learning journey features.

### Authentication

Most endpoints require authentication via Bearer Token (Laravel Sanctum).

```
Authorization: Bearer {token}
```

### Response Format

All endpoints return a consistent JSON envelope:

```json
{
  "status": true,
  "message": "Success message",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### Error Response

```json
{
  "status": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Localization

Use the `Accept-Language` header to specify locale:
- `en` (default)
- `ar`

---

## Endpoints

### System & Configuration

#### Health Check
```
GET /health
```
Returns system status.

**Response:**
```json
{
  "status": true,
  "data": {
    "status": "healthy",
    "version": "1.0.0",
    "server_time": "2026-02-10T10:30:00Z"
  }
}
```

#### App Configuration
```
GET /app-config
```
Returns public app settings and home sections configuration.

**Response:**
```json
{
  "status": true,
  "data": {
    "settings": {
      "app_name": "ق",
      "maintenance_mode": false,
      "features_enabled": ["lessons", "duas", "hadith", "quran"],
      "home_sections_order": ["daily_verse", "daily_hadith", "journey_progress"]
    },
    "home_sections": [
      {
        "key": "daily_verse",
        "title": "Verse of the Day",
        "type": "single",
        "source_type": "verses",
        "icon": "book-open",
        "route": "/quran/daily",
        "position": 1
      }
    ],
    "locale": "en",
    "server_time": "2026-02-10T10:30:00Z"
  }
}
```

#### Get Specific Setting
```
GET /app-config/settings/{key}
```
Returns a specific public setting.

#### Home Sections Only
```
GET /app-config/home-sections
```
Returns only home sections configuration.

---

### Authentication

#### Guest Login
```
POST /auth/guest
```
Authenticate as a guest user.

**Request:**
```json
{
  "device_id": "unique-device-identifier",
  "locale": "en"
}
```

#### Register
```
POST /auth/register
```
Register a new user account.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securepassword",
  "name": "User Name",
  "gender": "male",
  "birth_date": "1990-01-01",
  "locale": "en"
}
```

#### Login
```
POST /auth/login
```
Login with email and password.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

#### Social Login
```
POST /auth/social/{provider}
```
Login via social provider (google, facebook, apple).

**Request (Google):**
```json
{
  "id_token": "google-id-token"
}
```

**Request (Facebook):**
```json
{
  "access_token": "facebook-access-token"
}
```

**Request (Apple):**
```json
{
  "identity_token": "apple-identity-token"
}
```

#### Logout
```
POST /auth/logout
```
**Auth Required:** Yes

Revoke current access token.

---

### User Profile

#### Get Current User
```
GET /me
```
**Auth Required:** Yes

Returns current user profile.

#### Update Profile
```
PUT /me/profile
```
**Auth Required:** Yes

**Request:**
```json
{
  "name": "New Name",
  "gender": "male",
  "birth_date": "1990-01-01",
  "locale": "ar"
}
```

#### Get Onboarding Data
```
GET /me/onboarding
```
**Auth Required:** Yes

#### Update Onboarding
```
PUT /me/onboarding
```
**Auth Required:** Yes

**Request:**
```json
{
  "start_date": "2026-01-01",
  "shahada_date": "2025-12-01",
  "learning_goal": "learn_basics",
  "timezone": "America/New_York"
}
```

#### Get Settings
```
GET /me/settings
```
**Auth Required:** Yes

#### Update Settings
```
PUT /me/settings
```
**Auth Required:** Yes

**Request:**
```json
{
  "language": "ar",
  "dark_mode": true,
  "notifications_enabled": true,
  "time_format": "24h",
  "prayer_calc_method": 2,
  "prayer_madhab": 0
}
```

---

### Quran

#### List Surahs
```
GET /quran/surahs
```
Returns all 114 surahs.

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "number": 1,
      "name": "Al-Fatihah",
      "name_ar": "الفاتحة",
      "name_en": "Al-Fatihah",
      "verse_count": 7
    }
  ]
}
```

#### Get Surah with Verses
```
GET /quran/surahs/{surahNumber}
```

**Query Parameters:**
- `translation_id` - Filter by specific translation
- `per_page` - Items per page (default: 50, max: 100)
- `page` - Page number

#### Get Single Verse
```
GET /quran/verses/{id}
```

#### Get Verse by Reference
```
GET /quran/verses/{surah}/{ayah}
```
Example: `GET /quran/verses/2/255` for Ayatul Kursi

#### Get Available Languages
```
GET /quran/languages
```
Returns active languages with translation counts.

#### Search Verses
```
GET /quran/search
```
Supports Arabic search without diacritics.

**Query Parameters:**
- `q` (required) - Search term
- `surah` - Filter by surah number
- `per_page` - Items per page

#### Daily Verse
```
GET /quran/daily
```
Returns verse of the day.

---

### Hadith

#### List Collections
```
GET /hadith/collections
```
Returns available hadith collections/books.

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "key": "bukhari",
      "name": "Sahih al-Bukhari",
      "hadith_count": 7563
    }
  ]
}
```

#### Get Collection Hadiths
```
GET /hadith/collections/{collection}
```

#### List All Hadiths
```
GET /hadith
```

**Query Parameters:**
- `collection` - Filter by collection
- `per_page` - Items per page

#### Get Single Hadith
```
GET /hadith/{id}
```

#### Search Hadiths
```
GET /hadith/search
```

**Query Parameters:**
- `q` (required) - Search term
- `collection` - Filter by collection
- `per_page` - Items per page

#### Daily Hadith
```
GET /hadith/daily
```
Returns hadith of the day.

---

### Duas

#### List Duas
```
GET /duas
```

**Query Parameters:**
- `category_id` - Filter by category
- `featured` - Filter featured only (boolean)
- `q` - Search term
- `per_page` - Items per page

#### Get Single Dua
```
GET /duas/{id}
```
Returns dua with Quran and Hadith references.

#### Get Dua Categories
```
GET /duas/categories
```

#### Search Duas
```
GET /duas/search
```

**Query Parameters:**
- `q` (required) - Search term
- `per_page` - Items per page

---

### Adhkar

#### List Adhkar
```
GET /adhkar
```

**Query Parameters:**
- `category` - Filter by category key (morning, evening, sleep, etc.)
- `featured` - Filter featured only
- `q` - Search term
- `per_page` - Items per page

#### Get Single Dhikr
```
GET /adhkar/{id}
```

#### Get Adhkar Categories
```
GET /adhkar/categories
```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "key": "morning",
      "name": "Morning Adhkar",
      "icon": "sun",
      "count": 15
    }
  ]
}
```

#### Get Adhkar by Category
```
GET /adhkar/category/{category}
```
Example: `GET /adhkar/category/morning`

---

### Categories

#### List Categories
```
GET /categories
```

**Query Parameters:**
- `scope` - Filter by scope key (lessons, duas, daily_tasks)

#### Get Single Category
```
GET /categories/{id}
```

---

### Saved Items (Favorites)

**Auth Required:** Yes for all endpoints

#### List Saved Items
```
GET /saved
```

**Query Parameters:**
- `type` - Filter by type (dua, hadith, lesson, verse, adhkar)

#### Save Item
```
POST /saved/{type}/{itemId}
```
Types: `dua`, `hadith`, `lesson`, `verse`, `adhkar`

#### Remove Saved Item
```
DELETE /saved/{type}/{itemId}
```

---

### Lessons (Learning Journey)

**Auth Required:** Yes for all endpoints

#### List Lessons
```
GET /lessons
```

**Query Parameters:**
- `week` - Filter by week number
- `day` - Filter by day number
- `per_page` - Items per page

#### Get Today's Lesson
```
GET /lessons/today
```

#### Get Progress
```
GET /lessons/progress
```

**Response:**
```json
{
  "status": true,
  "data": {
    "lessons_completed": 15,
    "lessons_total": 40,
    "progress_percentage": 37,
    "current_streak": 7,
    "longest_streak": 14
  }
}
```

#### Get Single Lesson
```
GET /lessons/{id}
```

#### Mark Lesson Complete
```
POST /lessons/{id}/complete
```

#### Save Reflection
```
PUT /lessons/{id}/reflection
```

**Request:**
```json
{
  "reflection_text": "My thoughts on this lesson..."
}
```

---

### Home Dashboard

#### Get Dashboard Data
```
GET /home/dashboard
```

Returns aggregated home screen data.

**Response:**
```json
{
  "status": true,
  "data": {
    "sections": [...],
    "daily_verse": {
      "id": 255,
      "surah_number": 2,
      "ayah_number": 255,
      "ayah_key": "2:255",
      "surah_name": "Al-Baqarah",
      "text": "...",
      "text_ar": "..."
    },
    "daily_hadith": {
      "id": 1234,
      "collection": "bukhari",
      "hadith_number": 1,
      "text": "...",
      "text_ar": "..."
    },
    "progress": {
      "lessons_completed": 15,
      "lessons_total": 40,
      "progress_percentage": 37,
      "current_streak": 7
    },
    "server_time": "2026-02-10T10:30:00Z"
  }
}
```

---

### Prayer Times

#### Get Prayer Times
```
GET /prayer-times
```

**Query Parameters:**
- `lat` (required) - Latitude
- `lng` (required) - Longitude
- `date` - Date (YYYY-MM-DD, default: today)
- `method` - Calculation method (default: 2/ISNA)
- `madhab` - Asr calculation madhab (default: 0/Shafi)

#### Get Hijri Calendar
```
GET /calendar/hijri
```

---

### Events (Analytics)

#### Log Event
```
POST /events
```

**Request:**
```json
{
  "event_type": "lesson_completed",
  "entity_type": "lesson",
  "entity_id": "123",
  "meta": {
    "duration_seconds": 300
  }
}
```

---

## Arabic Search

The API supports searching Arabic text without diacritics (tashkeel).

**Example:**
- Searching for `بقرة` will match `بَقَرَةً`
- Searching for `الله` will match `اللَّه`

This works for:
- Quran verse search (`/quran/search`)
- Hadith search (`/hadith/search`)
- Dua search (`/duas/search`)
- Adhkar search (via `q` parameter)

---

## Rate Limiting

Search endpoints are rate-limited:
- 60 requests per minute for authenticated users
- 30 requests per minute for guests

---

## Changelog

### v1.0.0 (February 10, 2026)
- Initial release
- Added App Configuration endpoints
- Added Quran public API (surahs, verses, search, daily)
- Added Hadith public API (collections, items, search, daily)
- Added Duas public API (list, categories, search)
- Added Adhkar public API (list, categories, by-category)
- Added Home Dashboard endpoint
- Added verse and adhkar types to saved items
- Fixed Quran language priority (English > Arabic > others)
- Added Arabic search normalization (diacritics-free search)
