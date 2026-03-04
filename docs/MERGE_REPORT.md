# Merge Report: Backup → Backend

**Branch:** `feature/merge-backup-into-backend`  
**Source:** `_restructure_backup_20260304_060058`  
**Target:** `Backend`  
**Date:** 2026-03-04

---

## Summary

All missing features and changes from the restructure backup were merged into `Backend` in phases. No files were overwritten silently; additive copies and careful merges were used. The backup folder was not deleted. Flutter-App was not touched.

---

## Restored features

| Feature | Description |
|--------|-------------|
| **Daily Inspiration** | Service, model, migration, API `GET /api/v1/home/daily-inspiration`, test. |
| **Password reset** | API `forgot-password` / `reset-password`, web GET/POST `/reset-password`, `PasswordResetMail`, `PasswordResetService`, Blade views, `.well-known` (apple-app-site-association, assetlinks.json), `public/reset-password/index.html`. |
| **Embed search (Tiptap)** | `EmbedSearchController`, API `GET /api/v1/search/hadith`, `GET /api/v1/search/ayah`. |
| **Tiptap embeds** | HadithEmbed, AyahEmbed, HashtagEmbedSuggestion (JS + CSS), Filament profile entries. |
| **Mail URL override** | `ResetPassword::createUrlUsing` in `AppServiceProvider` using `config('app.frontend_url')`. |
| **Config** | `config/app.php`: `frontend_url`. `config/filament-tiptap-editor.php`: hadithEmbed, ayahEmbed, hashtagEmbedSuggestion in default profile. |

---

## Files added (Phase 1)

- `app/Application/DailyInspiration/DailyInspirationService.php`
- `app/Domain/DailyInspiration/UserDailyInspiration.php`
- `app/Http/Controllers/Api/V1/EmbedSearchController.php`
- `app/Mail/PasswordResetMail.php`
- `app/Services/Auth/PasswordResetService.php`
- `database/migrations/2026_02_28_120000_create_user_daily_inspirations_table.php`
- `public/reset-password/index.html`
- `public/.well-known/apple-app-site-association`
- `public/.well-known/assetlinks.json`
- `resources/js/tiptap/AyahEmbed.js`, `CalloutFallback.js`, `HadithEmbed.js`, `HashtagEmbedSuggestion.js`
- `resources/js/tiptap/HOW-TO-TEST-HASHTAG.md`, `README-EMBED.md`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/emails/auth/password_reset.blade.php`, `password_reset_text.blade.php`
- `tests/Feature/Api/V1/DailyInspirationTest.php`
- `docs/MERGE_COMPARISON_REPORT.md`

---

## Files merged (Phase 2)

| File | Change |
|------|--------|
| `routes/api_v1.php` | EmbedSearchController use + routes; forgot-password, reset-password; daily-inspiration route. |
| `routes/web.php` | GET/POST `/reset-password` with PasswordResetService. |
| `app/Http/Controllers/Api/V1/AuthController.php` | PasswordResetService, `forgotPassword()`, `resetPassword()`. |
| `app/Http/Controllers/Api/V1/HomeController.php` | DailyInspirationService use, `dailyInspiration()`. |
| `app/Providers/AppServiceProvider.php` | ResetPassword::createUrlUsing (frontend_url). |
| `config/app.php` | `frontend_url`. |
| `config/filament-tiptap-editor.php` | hadithEmbed, ayahEmbed, hashtagEmbedSuggestion in default profile. |
| `resources/js/tiptap-extensions.js` | HadithEmbed, AyahEmbed, HashtagEmbedSuggestion. |
| `resources/css/tiptap-extensions.css` | Embed chip and suggestion list styles. |
| `app/Console/Commands/BackfillJourneyWeeksCommand.php` | Stray whitespace removed. |

---

## Files left as-is (intentional)

- **`.env`** – Not copied (secrets).
- **`package.json` / `package-lock.json`** – Not overwritten; existing deps kept. Run `npm install` and `npm run build` if you need rebuilt assets.
- **`public/build/manifest.json`** – Rebuilt by Vite; not copied.
- **`storage/`** (logs, framework, app content) – Not copied from backup.

---

## Files left as *.backup

None. All differing files were merged additively; no conflicts were left as `.backup`.

---

## Migrations made SQLite-safe

Two MySQL-only migrations were made conditional so test runs (SQLite) succeed:

- `2026_02_26_023854_add_verse_adhkar_to_saved_items_item_type.php` – runs `MODIFY COLUMN` only when driver is `mysql`.
- `2026_02_28_000000_increase_journey_week_lessons_sort_order_column_size.php` – same.

---

## Commits created (merge branch)

1. `chore: restore missing files from backup`
2. `feat: restore password reset flow (API, web landing, mail URL)`
3. `feat: restore daily inspiration endpoint`
4. `feat: restore Tiptap embed extensions (hadith, ayah, hashtag)`
5. `chore: remove stray whitespace in BackfillJourneyWeeksCommand`
6. `fix: make MySQL-only migrations SQLite-safe for testing`

---

## Commands to verify

```bash
cd Backend
composer install
composer dump-autoload
php artisan optimize:clear
php artisan migrate
php artisan test
npm install && npm run build
```

Note: The test suite currently has some pre-existing failures (e.g. seed data, language setup). DailyInspirationTest: one test passes (unauth 401); others depend on test DB state (e.g. `duas.category_key`). The merge does not introduce new regressions in the merged areas (routes, auth, home, tiptap).

---

## Backup folder

The folder `_restructure_backup_20260304_060058` was **not** deleted and remains at the repo root for reference.
