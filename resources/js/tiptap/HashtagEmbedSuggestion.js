/**
 * Suggestion extension for #hadith / #ayah / #quran.
 * Uses custom findSuggestionMatch to detect command + query, fetches from API, renders list, inserts node on select.
 */
import { Extension } from '@tiptap/core';
import { PluginKey } from '@tiptap/pm/state';
import { Suggestion } from '@tiptap/suggestion';

const EMBED_API_BASE = typeof window !== 'undefined' && window.embedSearchBaseUrl != null
  ? window.embedSearchBaseUrl
  : '/api/v1';

const COMMANDS = ['hadith', 'ayah', 'quran'];
const RE = new RegExp(`#(${COMMANDS.join('|')})(?:\\s+(.*))?$`, 'i');

function findHashtagEmbedMatch(config) {
  const { $position } = config;
  const nodeBefore = $position.nodeBefore;
  const fullText = nodeBefore?.isText ? nodeBefore.text : '';
  if (!fullText) return null;

  const textFrom = $position.pos - fullText.length;
  const textBeforeCursor = fullText.slice(0, $position.pos - textFrom);

  const match = textBeforeCursor.match(RE);
  if (match) {
    const fullMatch = match[0];
    const from = textFrom + textBeforeCursor.indexOf(fullMatch);
    const to = $position.pos;
    const command = (match[1] || '').toLowerCase();
    const searchQuery = (match[2] || '').trim();
    return {
      range: { from, to },
      query: command === 'quran' ? `ayah:${searchQuery}` : `${command}:${searchQuery}`,
      text: fullMatch,
    };
  }

  if (/^#\s*$/.test(textBeforeCursor) || textBeforeCursor === '#') {
    const from = textFrom + textBeforeCursor.indexOf('#');
    return {
      range: { from, to: $position.pos },
      query: 'prompt:',
      text: textBeforeCursor.trimEnd(),
    };
  }
  return null;
}

function debounce(fn, ms) {
  let t;
  return function (...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), ms);
  };
}

let abortController = null;

async function searchHadith(query, limit = 10) {
  if (abortController) abortController.abort();
  abortController = new AbortController();
  const url = `${EMBED_API_BASE}/search/hadith?limit=${limit}${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  const res = await fetch(url, { signal: abortController.signal });
  if (!res.ok) return [];
  const json = await res.json();
  return Array.isArray(json.data) ? json.data : [];
}

async function searchAyah(query, limit = 10) {
  if (abortController) abortController.abort();
  abortController = new AbortController();
  const url = `${EMBED_API_BASE}/search/ayah?limit=${limit}${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  const res = await fetch(url, { signal: abortController.signal });
  if (!res.ok) return [];
  const json = await res.json();
  return Array.isArray(json.data) ? json.data : [];
}

const fetchItems = debounce(async ({ query }) => {
  const [mode, searchQuery] = query.includes(':') ? query.split(/:(.*)/s).map((s) => s?.trim() ?? '') : [query, ''];
  if (mode === 'prompt') return [];
  try {
    if (mode === 'hadith') return await searchHadith(searchQuery);
    if (mode === 'ayah' || mode === 'quran') return await searchAyah(searchQuery);
  } catch (e) {
    if (e?.name === 'AbortError') return [];
  }
  return [];
}, 250);

function positionList(listEl, clientRect) {
  if (!listEl || !clientRect) return;
  const rect = typeof clientRect === 'function' ? clientRect() : clientRect;
  if (!rect) return;
  listEl.style.position = 'fixed';
  listEl.style.left = `${rect.left}px`;
  listEl.style.top = `${rect.bottom + 4}px`;
  listEl.style.zIndex = '9999';
}

function createSuggestionList() {
  let listEl = null;
  let selectedIndex = 0;

  return {
    onStart(props) {
      if (typeof console !== 'undefined') {
        console.log('[Hashtag] suggestion start ✅', { query: props.query, range: props.range });
      }
      listEl = document.createElement('div');
      listEl.className = 'tiptap-embed-suggestion-list';
      listEl.setAttribute('role', 'listbox');
      document.body.appendChild(listEl);
      selectedIndex = 0;
      positionList(listEl, props.clientRect);
    },
    onUpdate(props) {
      if (!listEl) return;
      const { items, command, query, clientRect } = props;
      positionList(listEl, clientRect);
      listEl.innerHTML = '';
      listEl.setAttribute('aria-activedescendant', '');

      if (items.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'tiptap-embed-suggestion-list__empty';
        empty.textContent = query === 'prompt:' ? 'Type hadith, ayah, or quran' : (query ? 'No results' : 'Type to search…');
        listEl.appendChild(empty);
        return;
      }

      selectedIndex = Math.max(0, Math.min(selectedIndex, items.length - 1));
      items.forEach((item, i) => {
        const row = document.createElement('div');
        row.className = 'tiptap-embed-suggestion-list__item';
        row.setAttribute('role', 'option');
        row.setAttribute('id', `embed-suggestion-${i}`);
        if (i === selectedIndex) {
          row.classList.add('is-selected');
          listEl.setAttribute('aria-activedescendant', row.id);
        }
        const label = document.createElement('div');
        label.className = 'tiptap-embed-suggestion-list__label';
        label.textContent = item.label || '';
        const meta = document.createElement('div');
        meta.className = 'tiptap-embed-suggestion-list__meta';
        if (item.preview_ar) meta.textContent = item.preview_ar;
        else if (item.meta) meta.textContent = typeof item.meta === 'object' ? JSON.stringify(item.meta) : String(item.meta);
        row.appendChild(label);
        row.appendChild(meta);
        row.addEventListener('mousedown', (e) => {
          e.preventDefault();
          command(item);
        });
        listEl.appendChild(row);
      });
    },
    onKeyDown(props) {
      const { event, items } = props;
      if (items.length === 0) {
        if (event.key === 'Escape') return true;
        return false;
      }
      if (event.key === 'ArrowDown') {
        selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
        updateSelectedInList(listEl, selectedIndex);
        return true;
      }
      if (event.key === 'ArrowUp') {
        selectedIndex = Math.max(selectedIndex - 1, 0);
        updateSelectedInList(listEl, selectedIndex);
        return true;
      }
      if (event.key === 'Enter') {
        event.preventDefault();
        const item = items[selectedIndex];
        if (item) props.command(item);
        return true;
      }
      if (event.key === 'Escape') {
        return true;
      }
      return false;
    },
    onExit() {
      if (listEl?.parentNode) listEl.parentNode.removeChild(listEl);
      listEl = null;
      selectedIndex = 0;
    },
  };
}

function updateSelectedInList(listEl, index) {
  if (!listEl) return;
  const items = listEl.querySelectorAll('.tiptap-embed-suggestion-list__item');
  items.forEach((el, i) => el.classList.toggle('is-selected', i === index));
  const selected = items[index];
  if (selected) {
    listEl.setAttribute('aria-activedescendant', selected.id);
    selected.scrollIntoView({ block: 'nearest' });
  }
}

const HashtagEmbedSuggestionPluginKey = new PluginKey('hashtagEmbedSuggestion');

export const HashtagEmbedSuggestion = Extension.create({
  name: 'hashtagEmbedSuggestion',

  addOptions() {
    return {
      suggestion: {
        pluginKey: HashtagEmbedSuggestionPluginKey,
        char: '#',
        allowSpaces: true,
        allowedPrefixes: [' ', '\n'],
        startOfLine: false,
        decorationClass: 'tiptap-embed-suggestion-decoration',
        findSuggestionMatch: findHashtagEmbedMatch,
        items: fetchItems,
        command: ({ editor, range, props }) => {
          const item = props;
          if (typeof console !== 'undefined') {
            console.log('[Hashtag] selected ✅', item);
          }
          if (item.id != null && item.label != null) {
            editor
              .chain()
              .focus()
              .deleteRange(range)
              .insertContent({ type: 'hadithEmbed', attrs: { id: item.id, label: item.label, collection: item.meta?.collection ?? null } })
              .run();
          } else if (item.surah != null && item.ayah != null && item.label != null) {
            editor
              .chain()
              .focus()
              .deleteRange(range)
              .insertContent({ type: 'ayahEmbed', attrs: { surah: item.surah, ayah: item.ayah, label: item.label } })
              .run();
          }
        },
        render: () => createSuggestionList(),
      },
    };
  },

  addProseMirrorPlugins() {
    if (typeof console !== 'undefined') {
      console.log('[Tiptap] editor created ✅', this.editor);
      console.log('[Hashtag] extension mounted ✅');
    }
    return [
      Suggestion({
        editor: this.editor,
        ...this.options.suggestion,
      }),
    ];
  },
});
