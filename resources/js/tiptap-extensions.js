/**
 * Custom Tiptap extensions for Filament Tiptap Editor.
 * Built by Vite; loaded before the editor so window.TiptapEditorExtensions is set.
 */
import { Callout } from './tiptap/Callout.js';
import { HadithEmbed } from './tiptap/HadithEmbed.js';
import { AyahEmbed } from './tiptap/AyahEmbed.js';
import { HashtagEmbedSuggestion } from './tiptap/HashtagEmbedSuggestion.js';

window.TiptapEditorExtensions = {
  ...(window.TiptapEditorExtensions || {}),
  callout: [Callout],
  hadithEmbed: [HadithEmbed],
  ayahEmbed: [AyahEmbed],
  hashtagEmbedSuggestion: [HashtagEmbedSuggestion],
};
