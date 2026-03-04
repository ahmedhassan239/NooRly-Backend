/**
 * Inline ayah (Quran verse) embed node for Tiptap.
 * Renders as a chip; serializes to <span data-embed="ayah" data-surah="..." data-ayah="..." data-label="...">.
 */
import { Node, mergeAttributes, nodePasteRule } from '@tiptap/core';

export const AyahEmbed = Node.create({
  name: 'ayahEmbed',

  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,

  addOptions() {
    return {
      HTMLAttributes: {},
    };
  },

  addAttributes() {
    return {
      surah: {
        default: null,
        parseHTML: (el) => {
          const v = el.getAttribute('data-surah');
          return v != null ? parseInt(v, 10) : null;
        },
        renderHTML: (attrs) => (attrs.surah != null ? { 'data-surah': String(attrs.surah) } : {}),
      },
      ayah: {
        default: null,
        parseHTML: (el) => {
          const v = el.getAttribute('data-ayah');
          return v != null ? parseInt(v, 10) : null;
        },
        renderHTML: (attrs) => (attrs.ayah != null ? { 'data-ayah': String(attrs.ayah) } : {}),
      },
      label: {
        default: '',
        parseHTML: (el) => el.getAttribute('data-label') || '',
        renderHTML: (attrs) => (attrs.label ? { 'data-label': attrs.label } : {}),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'span[data-embed="ayah"]',
        getAttrs: (dom) => {
          const surah = dom.getAttribute('data-surah');
          const ayah = dom.getAttribute('data-ayah');
          return {
            surah: surah != null ? parseInt(surah, 10) : null,
            ayah: ayah != null ? parseInt(ayah, 10) : null,
            label: dom.getAttribute('data-label') || '',
          };
        },
      },
    ];
  },

  renderHTML({ node, HTMLAttributes }) {
    return [
      'span',
      mergeAttributes(
        { 'data-embed': 'ayah', contentEditable: 'false' },
        this.options.HTMLAttributes,
        HTMLAttributes,
        { class: 'tiptap-embed-chip tiptap-embed-chip--ayah' }
      ),
      node.attrs.label || 'Ayah',
    ];
  },

  addNodeView() {
    return ({ node }) => {
      const span = document.createElement('span');
      span.setAttribute('data-embed', 'ayah');
      span.setAttribute('contenteditable', 'false');
      span.className = 'tiptap-embed-chip tiptap-embed-chip--ayah';
      if (node.attrs.surah != null) span.setAttribute('data-surah', String(node.attrs.surah));
      if (node.attrs.ayah != null) span.setAttribute('data-ayah', String(node.attrs.ayah));
      if (node.attrs.label) span.setAttribute('data-label', node.attrs.label);

      const icon = document.createElement('span');
      icon.className = 'tiptap-embed-chip__icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = '☾';

      const labelSpan = document.createElement('span');
      labelSpan.className = 'tiptap-embed-chip__label';
      labelSpan.textContent = node.attrs.label || 'Ayah';

      span.appendChild(icon);
      span.appendChild(labelSpan);

      return { dom: span };
    };
  },

  addPasteRules() {
    return [
      nodePasteRule({
        find: /\{\{ayah:(\d+):(\d+)\}\}/g,
        type: this.type,
        getAttributes: (match) => ({
          surah: parseInt(match[1], 10),
          ayah: parseInt(match[2], 10),
          label: `Surah ${match[1]}:${match[2]}`,
        }),
      }),
    ];
  },
});
