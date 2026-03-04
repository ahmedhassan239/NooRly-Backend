# Inline Hashtag Embeds (Hadith / Ayah)

Tiptap extensions for embedding Hadith and Quran ayah references as inline chips in the Filament Tiptap editor.

## Usage in editor

- Type **#hadith** (optional: space + search term) → suggestion list for hadith; choose one to insert a chip.
- Type **#ayah** or **#quran** (optional: space + search term) → suggestion list for verses; choose one to insert a chip.

Popup: debounced search (250ms), arrow up/down, Enter to select, Escape to close.

## API base URL

Search requests use `/api/v1` by default. To override (e.g. for SPA or different origin), set before the editor loads:

```html
<script>
  window.embedSearchBaseUrl = 'https://your-api.example.com/api/v1';
</script>
```

## Backend endpoints

- `GET /api/v1/search/hadith?q=...&limit=10` → `{ data: [{ id, label, preview_ar, meta }] }`
- `GET /api/v1/search/ayah?q=...&limit=10` → `{ data: [{ surah, ayah, label, preview_ar, meta }] }`

## Stored HTML (no duplicated content)

Nodes serialize to data attributes only:

- Hadith: `<span data-embed="hadith" data-id="280" data-label="Sunan Abi Dawud #280" data-collection="abudawud"></span>`
- Ayah: `<span data-embed="ayah" data-surah="2" data-ayah="153" data-label="Al-Baqarah 2:153"></span>`

Backend/mobile can resolve `id` or `surah`/`ayah` to full text when rendering.

## Paste shortcodes (optional)

- `{{hadith:280}}` → hadith embed chip (id=280, label "Hadith #280").
- `{{ayah:2:153}}` → ayah embed chip (surah=2, ayah=153).

## Enabling in Filament

Extensions are registered in `resources/js/tiptap-extensions.js` and loaded via `config('filament-tiptap-editor.extensions_script')`. No toolbar change; they work as soon as the script is built and the editor loads.
