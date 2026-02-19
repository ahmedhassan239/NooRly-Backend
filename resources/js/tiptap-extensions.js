/**
 * Custom Tiptap extensions for Filament Tiptap Editor.
 * Built by Vite; loaded before the editor so window.TiptapEditorExtensions is set.
 */
import { Callout } from './tiptap/Callout.js';

window.TiptapEditorExtensions = {
  ...(window.TiptapEditorExtensions || {}),
  callout: [Callout],
};
