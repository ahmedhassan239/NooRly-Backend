<?php

namespace App\Livewire\Filament\Forms\Components;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use Livewire\Component;
use Livewire\Attributes\On;

class ReligiousReferenceModal extends Component
{
    public bool $isOpen = false;
    public string $type = ''; // 'ayah' or 'hadith'
    public string $searchTerm = '';
    public array $results = [];
    public bool $isSearching = false;

    protected QuranSearchServiceInterface $quranService;
    protected HadithSearchServiceInterface $hadithService;

    public function boot(
        QuranSearchServiceInterface $quranService,
        HadithSearchServiceInterface $hadithService
    ): void {
        $this->quranService = $quranService;
        $this->hadithService = $hadithService;
    }

    #[On('open-religious-modal')]
    public function openModal(array $data): void
    {
        $this->type = $data['type'] ?? '';
        $this->isOpen = true;
        $this->searchTerm = '';
        $this->results = [];
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->type = '';
        $this->searchTerm = '';
        $this->results = [];
    }

    public function updatedSearchTerm(): void
    {
        $this->search();
    }

    public function search(): void
    {
        $term = trim($this->searchTerm);
        
        if (mb_strlen($term) < 2) {
            $this->results = [];
            return;
        }

        $this->isSearching = true;

        try {
            if ($this->type === 'ayah') {
                $this->results = $this->quranService->searchArabicVerses($term, 50);
            } elseif ($this->type === 'hadith') {
                $this->results = $this->hadithService->searchArabicHadith($term, 50);
            }
        } catch (\Exception $e) {
            $this->results = [];
        } finally {
            $this->isSearching = false;
        }
    }

    public function selectItem(int $id, string $label): void
    {
        $this->dispatch('insert-religious-reference', [
            'type' => $this->type,
            'id' => $id,
            'label' => $label,
        ]);

        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.filament.forms.components.religious-reference-modal');
    }
}
