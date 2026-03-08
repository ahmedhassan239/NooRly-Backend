# Adhkar import commands

Commands to import **Adhkar category data** and **Adhkar item data** from `Adhkar_Categories_Noorly.json`. Import is safe and non-destructive: it only creates/updates scope_id=4 (adhkar) categories and adhkar rows; it never changes Duas or scope_id=3.

---

## 1. Dry-run (preview only, no DB writes)

Shows what would be imported: categories with scope_id 3 vs 4, would-create/would-reuse counts, adhkar and categorizables counts.

```bash
# With explicit file path
php artisan adhkar:import-from-json --file=/path/to/Adhkar_Categories_Noorly.json

# Using config (set ADHKAR_IMPORT_JSON_PATH in .env)
php artisan adhkar:import-from-json
```

---

## 2. Execute import (categories + adhkar + categorizables)

Imports:

- **Adhkar categories** under `scope_id = 4` (creates new or reuses by slug)
- **Category translations** (en + ar) for each Adhkar category
- **Adhkar items** (text, count, reward, source, audio_url, position, etc.)
- **Categorizables** rows linking each Adhkar item to its category

```bash
# With explicit file path
php artisan adhkar:import-from-json --file=/path/to/Adhkar_Categories_Noorly.json --execute

# Using config
php artisan adhkar:import-from-json --execute
```

Example with the usual download path:

```bash
php artisan adhkar:import-from-json --file=/home/ahmed-hassan/Downloads/Adhkar_Categories_Noorly.json --execute
```

---

## 3. Optional: custom DB connection

```bash
php artisan adhkar:import-from-json --file=/path/to/Adhkar_Categories_Noorly.json --execute --connection=mysql
```

---

## Summary

| Goal                         | Command                                                                 |
|-----------------------------|-------------------------------------------------------------------------|
| Preview (dry-run)           | `php artisan adhkar:import-from-json [--file=...]`                      |
| Import categories + adhkar  | `php artisan adhkar:import-from-json [--file=...] --execute`            |

All writes run in a single transaction; on failure everything is rolled back.
