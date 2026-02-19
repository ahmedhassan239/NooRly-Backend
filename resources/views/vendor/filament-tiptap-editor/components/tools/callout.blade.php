<x-filament-tiptap-editor::dropdown-button
    label="{{ __('Callout') }}"
    active="callout"
    icon="callout"
    indicator="editor().isActive('callout', updatedAt) && editor().isFocused ? editor().getAttributes('callout').type : null"
    :list="false"
>
    <x-filament-tiptap-editor::button
        action="editor().chain().focus().setCallout('info').run()"
        :secondary="true"
        label="{{ __('Info') }}"
    />
    <x-filament-tiptap-editor::button
        action="editor().chain().focus().setCallout('warning').run()"
        :secondary="true"
        label="{{ __('Warning') }}"
    />
    <x-filament-tiptap-editor::button
        action="editor().chain().focus().setCallout('success').run()"
        :secondary="true"
        label="{{ __('Success') }}"
    />
    <x-filament-tiptap-editor::button
        action="editor().chain().focus().unsetCallout().run()"
        :secondary="true"
        label="{{ __('Remove callout') }}"
    />
</x-filament-tiptap-editor::dropdown-button>
