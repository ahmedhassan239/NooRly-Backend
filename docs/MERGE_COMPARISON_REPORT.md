# Merge Comparison Report: Backup → Backend

**Branch:** `feature/merge-backup-into-backend`  
**Source:** `_restructure_backup_20260304_060058` (pre-restructure snapshot)  
**Target:** `Backend`  
**Excluded from comparison:** `vendor/`, `node_modules/`, `storage/logs/`, `storage/framework/`, `.env`, `.DS_Store`, `.git/`

---

## A) Files only in BACKUP (to be restored into Backend)

| Path | Action |
|------|--------|
| app/Application/DailyInspiration/DailyInspirationService.php | Phase 1 copy |
| app/Domain/DailyInspiration/UserDailyInspiration.php | Phase 1 copy |
| app/Http/Controllers/Api/V1/EmbedSearchController.php | Phase 1 copy |
| app/Mail/PasswordResetMail.php | Phase 1 copy |
| app/Services/Auth/PasswordResetService.php | Phase 1 copy |
| database/migrations/2026_02_28_120000_create_user_daily_inspirations_table.php | Phase 1 copy |
| public/reset-password/index.html | Phase 1 copy |
| public/.well-known/apple-app-site-association | Phase 1 copy |
| public/.well-known/assetlinks.json | Phase 1 copy |
| resources/js/tiptap/AyahEmbed.js | Phase 1 copy |
| resources/js/tiptap/CalloutFallback.js | Phase 1 copy |
| resources/js/tiptap/HadithEmbed.js | Phase 1 copy |
| resources/js/tiptap/HashtagEmbedSuggestion.js | Phase 1 copy |
| resources/js/tiptap/HOW-TO-TEST-HASHTAG.md | Phase 1 copy |
| resources/js/tiptap/README-EMBED.md | Phase 1 copy |
| resources/views/auth/reset-password.blade.php | Phase 1 copy |
| resources/views/emails/auth/password_reset.blade.php | Phase 1 copy |
| resources/views/emails/auth/password_reset_text.blade.php | Phase 1 copy |
| tests/Feature/Api/V1/DailyInspirationTest.php | Phase 1 copy |

**Not copied (by design):**  
- database/database.sqlite (local DB)  
- .env (secrets)  
- public/build/* (rebuilt via npm)  
- storage/logs, storage/framework/* (runtime)  
- storage/app/content/*, storage/app/schema/* (can be regenerated; optional restore later)

---

## B) Files only in Backend

Backend has extra paths: `_conflicts_backup_*`, `.cursor/`, and generated/vendor paths. No action (keep as-is).

---

## C) Files in BOTH but DIFFERENT (Phase 2 merge)

| Path | Risk | Notes |
|------|------|--------|
| app/Console/Commands/BackfillJourneyWeeksCommand.php | Low | Likely whitespace/trivial |
| app/Http/Controllers/Api/V1/AuthController.php | High | Auth + password reset |
| app/Http/Controllers/Api/V1/HomeController.php | High | Home + daily inspiration |
| app/Providers/AppServiceProvider.php | Medium | Mail override |
| app/Providers/Filament/AdminPanelProvider.php | Medium | Tiptap config |
| config/app.php | Low | App config |
| config/filament-tiptap-editor.php | High | Tiptap extensions |
| .env.example | Low | Add any new keys |
| .gitignore | Low | Merge patterns |
| package.json | Medium | JS deps |
| package-lock.json | Medium | Lockfile |
| public/build/manifest.json | Skip | Rebuilt by npm |
| resources/css/tiptap-extensions.css | Medium | Tiptap styles |
| resources/js/tiptap-extensions.js | High | Tiptap embeds |
| routes/api_v1.php | High | API routes |
| routes/web.php | Medium | Web routes |

---

*Generated for merge branch feature/merge-backup-into-backend.*
