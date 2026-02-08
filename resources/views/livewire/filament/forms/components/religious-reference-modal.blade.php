<div>
    <x-filament::modal id="religious-reference-modal" width="2xl" x-data="{ isOpen: @entangle('isOpen') }" x-show="isOpen" x-cloak>
        <x-slot name="heading">
            {{ $type === 'ayah' ? 'Insert Quran Ayah' : 'Insert Hadith' }}
        </x-slot>

        <x-slot name="description">
            Search and select a {{ $type === 'ayah' ? 'Quran verse' : 'Hadith item' }} to insert into the editor.
        </x-slot>

        <div class="space-y-4">
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.300ms="searchTerm"
                        placeholder="Search by Arabic text..."
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            @if($isSearching)
                <div class="text-center py-8">
                    <x-filament::loading-indicator class="w-8 h-8 mx-auto" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Searching...</p>
                </div>
            @elseif(empty($searchTerm) || mb_strlen($searchTerm) < 2)
                <div class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                    Type at least 2 characters to search
                </div>
            @elseif(empty($results))
                <div class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                    No results found
                </div>
            @else
                <div class="max-h-96 overflow-y-auto space-y-2">
                    @foreach($results as $id => $label)
                        <button
                            type="button"
                            wire:click="selectItem({{ $id }}, @js($label))"
                            class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-colors dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $label }}
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <x-filament::button wire:click="closeModal">
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('insert-religious-reference', (data) => {
                if (window.insertReligiousReference) {
                    window.insertReligiousReference(data.type, data.id, data.label);
                }
            });
        });
    </script>
</div>
