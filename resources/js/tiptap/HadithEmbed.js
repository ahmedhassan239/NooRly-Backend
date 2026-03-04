/**
 * Inline hadith embed node for Tiptap.
 * Renders as a chip; serializes to <span data-embed="hadith" data-id="..." data-label="..." data-collection="...">.
 */
import { Node, mergeAttributes, nodePasteRule } from '@tiptap/core';

export const HadithEmbed = Node.create({
  name: 'hadithEmbed',

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
      id: {
        default: null,
        parseHTML: (el) => {
          const v = el.getAttribute('data-id');
          return v != null ? parseInt(v, 10) : null;
        },
        renderHTML: (attrs) => (attrs.id != null ? { 'data-id': String(attrs.id) } : {}),
      },
      label: {
        default: '',
        parseHTML: (el) => el.getAttribute('data-label') || '',
        renderHTML: (attrs) => (attrs.label ? { 'data-label': attrs.label } : {}),
      },
      collection: {
        default: null,
        parseHTML: (el) => el.getAttribute('data-collection') || null,
        renderHTML: (attrs) => (attrs.collection ? { 'data-collection': attrs.collection } : {}),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'span[data-embed="hadith"]',
        getAttrs: (dom) => {
          const id = dom.getAttribute('data-id');
          return {
            id: id != null ? parseInt(id, 10) : null,
            label: dom.getAttribute('data-label') || '',
            collection: dom.getAttribute('data-collection') || null,
          };
        },
      },
    ];
  },

  renderHTML({ node, HTMLAttributes }) {
    return [
      'span',
      mergeAttributes(
        { 'data-embed': 'hadith', contentEditable: 'false' },
        this.options.HTMLAttributes,
        HTMLAttributes,
        { class: 'tiptap-embed-chip tiptap-embed-chip--hadith' }
      ),
      node.attrs.label || 'Hadith',
    ];
  },

  addNodeView() {
    return ({ node, editor }) => {
      const span = document.createElement('span');
      span.setAttribute('data-embed', 'hadith');
      span.setAttribute('contenteditable', 'false');
      span.className = 'tiptap-embed-chip tiptap-embed-chip--hadith';
      if (node.attrs.id != null) span.setAttribute('data-id', String(node.attrs.id));
      if (node.attrs.label) span.setAttribute('data-label', node.attrs.label);
      if (node.attrs.collection) span.setAttribute('data-collection', node.attrs.collection);

      const icon = document.createElement('span');
      icon.className = 'tiptap-embed-chip__icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = '📖';

      const labelSpan = document.createElement('span');
      labelSpan.className = 'tiptap-embed-chip__label';
      labelSpan.textContent = node.attrs.label || 'Hadith';

      span.appendChild(icon);
      span.appendChild(labelSpan);

      return { dom: span };
    };
  },

  addPasteRules() {
    return [
      nodePasteRule({
        find: /\{\{hadith:(\d+)\}\}/g,
        type: this.type,
        getAttributes: (match) => ({
          id: parseInt(match[1], 10),
          label: `Hadith #${match[1]}`,
          collection: null,
        }),
      }),
    ];
  },
});
