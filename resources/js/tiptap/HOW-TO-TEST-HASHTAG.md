# How to test Hashtag (#hadith / #ayah) in Tiptap

## Stack (for reference)

- **Laravel** + **Filament** admin panel (Blade + Livewire).
- **awcodes/filament-tiptap-editor**: Tiptap runs inside an Alpine.js component loaded via `x-load-src`.
- **Custom extensions** are in `resources/js/tiptap-extensions.js` (Vite entry). They set `window.TiptapEditorExtensions` so the package’s bundle can merge them when creating the editor.
- No React/Vue/Inertia; plain JavaScript only.

## 1) Install and build

```bash
cd /path/to/Backend
npm install
npm run dev
```

Keep `npm run dev` running so Vite serves the extension script (and the panel can load it).

For production-style check:

```bash
npm run build
# then open the admin (no Vite dev server)
```

## 2) Open the admin editor page

1. Log in to the Filament admin panel (e.g. `/admin`).
2. Open any resource/form that uses the Tiptap editor (e.g. a page with `TiptapEditor::make(...)`).

## 3) Confirm extension script and editor in the console

Open the browser devtools console (F12). You should see, in order:

- `[Tiptap] module loaded ✅`  
  → The `tiptap-extensions.js` bundle ran and set `window.TiptapEditorExtensions`.
- `[Tiptap] TiptapEditorExtensions registered ✅` plus a list of keys  
  → Confirms `hadithEmbed`, `ayahEmbed`, `hashtagEmbedSuggestion` (and any others) are registered.
- When the editor is created and the hashtag extension is mounted:
  - `[Tiptap] editor created ✅` (with editor object)
  - `[Hashtag] extension mounted ✅`

If you never see `[Tiptap] module loaded ✅`, the extension script is not loading. Check:

- `config/filament-tiptap-editor.php`: `extensions_script` should be `'resources/js/tiptap-extensions.js'`.
- Admin panel injects the script in HEAD (see `AdminPanelProvider::ensureTiptapExtensionsLoad()`).
- With `npm run dev`, the script URL should point at the Vite dev server (e.g. `http://localhost:5173/...`). With `npm run build`, it should point at a built asset in `/build/`.

## 4) Test typing `#` and `#hadith` / `#ayah`

1. Click inside the Tiptap editor.
2. Type **`#`**.
   - You should see: `[Hashtag] suggestion start ✅` with `{ query: 'prompt:', range }` in the console.
   - The popup should open with the hint: **“Type hadith, ayah, or quran”**.
3. Type **`#hadith`** (or **`#hadith`** plus a space and a search term, e.g. `#hadith mercy`).
   - Suggestion list shows hadith results or “Type to search…”.
4. Type **`#ayah`** or **`#quran`** (optionally with a search term).
   - Same behaviour for ayah/verse search.
5. Use **Arrow Up/Down** to move, **Enter** to select, **Escape** to close.
6. On **Enter** you should see: `[Hashtag] selected ✅` and the trigger text should be replaced by a hadith/ayah chip.

The suggestion plugin uses **`char: '#'`** and opens on **`#`** (shows hint) and on **`#hadith`** / **`#ayah`** / **`#quran`** (with optional search query).

## 5) Test “Insert Test Hadith” (local only)

When **`APP_ENV=local`** (or your env is `local`), the toolbar shows an extra button: **“Insert Test Hadith”**.

1. Click it.
2. A chip with label “Test Hadith” should be inserted at the cursor.
3. This confirms the `hadithEmbed` node is registered and the editor can insert it.

The button is only rendered when `@env('local')` is true in the Blade view, so it is not shown in production.

## Troubleshooting

| Symptom | What to check |
|--------|----------------|
| No `[Tiptap] module loaded ✅` | Extension script not loaded. Check Vite (dev or build), `extensions_script` config, and HEAD script tag in admin layout. |
| No `[Hashtag] extension mounted ✅` | Editor created but our extension not in the list. Ensure `hashtagEmbedSuggestion` is in `window.TiptapEditorExtensions` and the package merges it (see plugin.js: `editorExtensions = {...coreExtensions, ...customExtensions}`). |
| Typing `#` does nothing | Check console for `[Hashtag] suggestion start ✅`. If missing, the extension may not be in the editor; ensure `hashtagEmbedSuggestion` is in `window.TiptapEditorExtensions`. |
| “Insert Test Hadith” not visible | Only shown when `APP_ENV=local`. Set `APP_ENV=local` in `.env` and clear config cache. |
