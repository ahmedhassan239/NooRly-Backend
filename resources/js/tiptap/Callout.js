import { Node, mergeAttributes } from '@tiptap/core';

const CALLOUT_TYPES = ['note', 'info', 'success', 'warning', 'danger'];

export const Callout = Node.create({
  name: 'callout',

  group: 'block',

  content: 'block+',

  defining: true,

  addOptions() {
    return {
      HTMLAttributes: {},
    };
  },

  addAttributes() {
    return {
      type: {
        default: 'note',
        parseHTML: (element) => element.getAttribute('data-callout') || 'note',
        renderHTML: (attributes) => {
          const type = CALLOUT_TYPES.includes(attributes.type) ? attributes.type : 'note';
          return {
            'data-callout': type,
            class: `callout callout-${type}`,
          };
        },
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-callout]',
        getAttrs: (dom) => {
          const type = dom.getAttribute('data-callout') || 'note';
          return { type: CALLOUT_TYPES.includes(type) ? type : 'note' };
        },
      },
      {
        tag: 'blockquote[data-callout]',
        getAttrs: (dom) => {
          const type = dom.getAttribute('data-callout') || 'note';
          return { type: CALLOUT_TYPES.includes(type) ? type : 'note' };
        },
      },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    const type = CALLOUT_TYPES.includes(HTMLAttributes['data-callout']) ? HTMLAttributes['data-callout'] : 'note';
    return [
      'div',
      mergeAttributes(this.options.HTMLAttributes, {
        'data-callout': type,
        class: `callout callout-${type}`,
      }, HTMLAttributes),
      0,
    ];
  },

  addCommands() {
    return {
      setCallout:
        (type) =>
        ({ state, chain, commands }) => {
          const t = CALLOUT_TYPES.includes(type) ? type : 'note';
          const { selection } = state;
          const { $from, $to } = selection;
          const range = $from.blockRange($to);
          if (range) {
            const slice = state.doc.slice(range.start, range.end);
            const content = slice.content.toJSON();
            return chain()
              .insertContentAt(
                { from: range.start, to: range.end },
                { type: this.name, attrs: { type: t }, content: content && content.length ? content : [{ type: 'paragraph' }] }
              )
              .setTextSelection(range.start + 1)
              .run();
          }
          return commands.insertContent({ type: this.name, attrs: { type: t }, content: [{ type: 'paragraph' }] });
        },

      toggleCallout:
        (type) =>
        ({ commands }) => {
          if (this.editor.isActive(this.name)) {
            return commands.unsetCallout();
          }
          return commands.setCallout(type);
        },

      unsetCallout:
        () =>
        ({ state, chain }) => {
          const { selection } = state;
          const pos = selection.$anchor;
          for (let d = pos.depth; d > 0; d--) {
            const node = pos.node(d);
            if (node.type === this.type) {
              const from = pos.before(d);
              const to = from + node.nodeSize;
              const content = node.content.toJSON();
              return chain()
                .insertContentAt({ from, to }, content && content.length ? content : [{ type: 'paragraph' }])
                .setTextSelection(from)
                .run();
            }
          }
          return false;
        },
    };
  },
});
