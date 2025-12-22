# Filament Bilingual Tabs UI - Implementation Guide

## Installation Complete ✅

### 1. TinyEditor Plugin Installed
```bash
composer require mohamedsabil83/filament-forms-tinyeditor
```

### 2. Files Created/Updated

**Trait** (Reusable):
- `app/Filament/Concerns/HasTranslatableTabs.php`

**Resources**:
- `app/Filament/Resources/LessonResource.php` ✅ Updated with Tabs
- `app/Filament/Resources/LessonResource/Pages/CreateLesson.php` ✅ Translation saving
- `app/Filament/Resources/LessonResource/Pages/EditLesson.php` ✅ Translation load/save

---

## How It Works

### Form Structure
```
├── Lesson Settings (Section)
│   ├── Day Number
│   ├── Type (Text/Video)
│   ├── Video URL
│   └── Duration
│
└── Translations (Tabs)
    ├── EN Tab (Required)
    │   ├── Title*
    │   ├── Slug (auto-generated)
    │   ├── Customize Slug (toggle)
    │   ├── Short Description (300 chars)
    │   └── Content (TinyEditor)
    │
    └── AR Tab (Optional, RTL)
        ├── Title
        ├── Slug
        ├── Customize Slug
        ├── Short Description (300 chars)
        └── Content (TinyEditor - RTL)
```

### Translation Saving
1. **Create**: Extracts `en_title`, `ar_title` etc. → saves to `lesson_translations`
2. **Edit**: Loads from `lesson_translations` → prefixes with lang code → saves back

### TinyEditor Features
- Image uploads → `public/lessons/images/`
- RTL support for Arabic
- Toolbar: Headings, Bold/Italic, Lists, Links, Images

---

## Apply to Other Resources

Copy the pattern for `DuaResource`, `DailyTaskResource`, `FaqResource`:

```php
use App\Filament\Concerns\HasTranslatableTabs;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

class DuaResource extends Resource
{
    use HasTranslatableTabs;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Non-translatable fields here
            
            static::getTranslationTabs(function ($langCode, $is Required) {
                return [
                    // Translatable fields with "{$langCode}_" prefix
                ];
            }),
        ]);
    }
}
```

Update Create/Edit pages the same way.

---

## Configuration

### TinyEditor Config (Optional)
Publish config: `php artisan vendor:publish --tag=filament-forms-tinyeditor-config`

### Image Storage
Default: `public/lessons/images/`  
Change via: `->fileAttachmentsDirectory('custom/path')`

---

## Testing

1. Visit `/admin/lessons/create`
2. See EN/AR tabs
3. Fill EN fields (required)
4. Switch to AR tab (RTL)
5. Save → Check database `lesson_translations` table

---

## Next Steps

- Apply same pattern to DuaResource
- Apply to DailyTaskResource
- Apply to FaqResource/FaqCategoryResource
- Style customization (CSS for tabs if needed)
