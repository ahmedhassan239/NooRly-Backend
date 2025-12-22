# i18n Database Migration Guide

## Overview
The i18n implementation uses **translation tables** (not JSON) for unlimited language support with strong MySQL filtering/search.

## Already Created Migrations

### 1. Languages Table
**File**: `2025_12_22_101332_create_languages_table.php`
- Stores all supported languages
- Enforces single default language
- Tracks active/inactive status

### 2. Translation Tables (5 files)
- `2025_12_22_101746_create_lesson_translations_table.php`
- `2025_12_22_101750_create_dua_translations_table.php`
- `2025_12_22_101751_create_daily_task_translations_table.php`
- `2025_12_22_101753_create_faq_category_translations_table.php`
- `2025_12_22_101755_create_faq_translations_table.php`

Each with:
- Foreign keys to base table & `languages.code`
- UNIQUE constraint on (entity_id, language_code)
- Indexes for performance

## Migration Strategy (SAFE)

### Step 1: Run New Migrations
```bash
php artisan migrate
```
This creates `languages` and `*_translations` tables **without touching existing data**.

### Step 2: Seed Languages
```bash
php artisan db:seed --class=LanguageSeeder
```
Creates `en` (default) and `ar` languages.

### Step 3: Backfill Existing Data
```bash
# Preview changes first
php artisan i18n:backfill --dry-run

# Apply backfill
php artisan i18n:backfill
```
This copies data from old columns (`title`, `content`, etc.) into English translations.

### Step 4: Verify Data
```bash
# Check translations were created
php artisan tinker
>>> App\Domain\Lessons\Lesson::first()->translations()->count()
```

### Step 5: Drop Old Columns (OPTIONAL - after verification)
**⚠️ ONLY after verifying translations work!**

Create a new migration:
```bash
php artisan make:migration drop_old_translatable_columns_from_base_tables
```

```php
public function up()
{
    Schema::table('lessons', function (Blueprint $table) {
        $table->dropColumn(['title', 'content']);
    });
    
    Schema::table('duas', function (Blueprint $table) {
        $table->dropColumn(['title', 'translation', 'transliteration', 'category']);
    });
    
    Schema::table('daily_tasks', function (Blueprint $table) {
        $table->dropColumn('title');
    });
    
    Schema::table('faq_categories', function (Blueprint $table) {
        $table->dropColumn('name');
    });
    
    Schema::table('faqs', function (Blueprint $table) {
        $table->dropColumn(['question', 'answer']);
    });
}

public function down()
{
    // Recreate columns (no data recovery)
    Schema::table('lessons', function (Blueprint $table) {
        $table->string('title');
        $table->json('content');
    });
    // ... etc
}
```

## Rollback Plan

If you need to rollback:
```bash
# Rollback translation tables (safe, doesn't touch old data)
php artisan migrate:rollback --step=6

# Or rollback everything
php artisan migrate:fresh
```

## Testing

Run i18n tests to verify:
```bash
php artisan test --filter=I18n
```

## File Locations

**Migrations**: `database/migrations/2025_12_22_*`  
**Seeders**: `database/seeders/LanguageSeeder.php`  
**Command**: `app/Console/Commands/BackfillTranslationsCommand.php`  
**Models**: `app/Domain/*/LessonTranslation.php` (etc.)  
**Trait**: `app/Domain/Traits/HasTranslations.php`

## Key Points

✅ **Non-destructive**: Old columns remain until you explicitly drop them  
✅ **Reversible**: Can rollback migrations safely  
✅ **Testable**: Use `--dry-run` flag before applying  
✅ **Zero downtime**: API continues working during migration
