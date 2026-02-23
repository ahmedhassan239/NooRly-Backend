<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\DomainServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    // Explicitly load so view namespace "filament-tiptap-editor" is always registered (fixes production "No hint path" error)
    \FilamentTiptapEditor\FilamentTiptapEditorServiceProvider::class,
];
