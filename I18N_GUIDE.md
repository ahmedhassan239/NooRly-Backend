# i18n Guide: Adding New Languages

## Overview
This project uses a translation table architecture that allows unlimited languages without schema changes.

## Adding a New Language

### 1. Add Language Record

Via Filament Admin:
1. Navigate to **Settings → Languages**
2. Click **New Language**
3. Fill in:
   - **Code**: ISO language code (e.g., `fr`, `es`, `ur`)
   - **Name**: English name (e.g., "French", "Spanish")
   - **Native Name**: Native language name (e.g., "Français", "Español")
   - **Direction**: `ltr` or `rtl`
   - **Active**: Toggle on
   - **Default**: Only set for one language
4. Save

Via Database Seeder:
```php
Language::create([
    'code' => 'fr',
    'name' => 'French',
    'native_name' => 'Français',
    'direction' => 'ltr',
    'is_active' => true,
    'is_default' => false,
]);
```

### 2. Add Translations

For each content entity (Lesson, Dua, DailyTask, FAQ, etc.), add translation records:

```php
$lesson->translations()->create([
    'language_code' => 'fr',
    'title' => 'Titre de la leçon',
    'short_description' => 'Description courte',
    'content' => json_encode(['content' => 'Contenu français']),
]);
```

**That's it!** No migrations needed. The system automatically:
- Resolves the new language via middleware
- Applies COALESCE fallback to English if translation missing
- Supports search/filter/sort on the new language

## API Usage

Clients can request any active language:

```bash
# Query parameter (highest priority)
GET /api/v1/lessons?lang=fr

# Accept-Language header
GET /api/v1/lessons
Headers: Accept-Language: fr-FR,fr;q=0.9

# X-Lang header
GET /api/v1/lessons
Headers: X-Lang: fr
```

Response includes resolved language:
```json
{
  "data": [...],
  "meta": {
    "lang": "fr",
    "fallback_lang": null
  }
}
```

## Fallback Behavior

If a translation is missing:
1. System tries requested language
2. Falls back to English (default)
3. Returns `fallback_lang` in meta

Example: Request `?lang=fr` but French translation missing → returns English content with `meta.fallback_lang: "en"`

## Best Practices

1. **Always provide English translations** - it's the fallback language
2. **Use consistent terminology** across languages
3. **Test fallback** by intentionally missing translations
4. **RTL languages** (Arabic, Urdu, Hebrew) - set `direction: 'rtl'`
5. **Pluralization** - handle in application logic or use JSON content structure
