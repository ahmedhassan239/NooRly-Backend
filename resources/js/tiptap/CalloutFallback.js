/**
 * Minimal fallback for "callout" node so stored content with callout nodes does not crash the editor.
 * Use this in place of Callout in window.TiptapEditorExtensions if the full Callout extension
 * is not available (e.g. script load order). Renders as blockquote.
 */
import { Node, mergeAttributes } from '@tiptap/core';

export const CalloutFallback = Node.create({
  name: 'callout',

  group: 'block',

  content: 'block+',

  defining: true,

  addAttributes() {
    return {
      type: {
        default: 'note',
        parseHTML: (el) => el.getAttribute('data-callout') || el.getAttribute('data-type') || 'note',
        renderHTML: (attrs) => ({ 'data-callout': attrs.type || 'note' }),
      },
    };
  },

  parseHTML() {
    return [
      { tag: 'div[data-callout]' },
      { tag: 'div[data-type="callout"]' },
      { tag: 'blockquote[data-callout]' },
      { tag: 'blockquote[data-type="callout"]' },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'blockquote',
      mergeAttributes(
        { class: 'callout-fallback' },
        { 'data-callout': HTMLAttributes['data-callout'] || HTMLAttributes.type || 'note' },
        HTMLAttributes
      ),
      0,
    ];
  },
});
