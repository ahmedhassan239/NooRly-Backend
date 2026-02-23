<?php

namespace App\Filament\Resources\HadithCollectionResource\Pages;

use App\Filament\Resources\HadithCollectionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateHadithCollection extends CreateRecord
{
    protected static string $resource = HadithCollectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->hadithItemIds = $data['hadithItemIds'] ?? [];
        $this->translationsData = [
            'en' => [
                'title' => $data['en_title'] ?? '',
                'description' => $data['en_description'] ?? null,
                'slug' => $data['en_slug'] ?? Str::slug($data['en_title'] ?? ''),
            ],
            'ar' => [
                'title' => $data['ar_title'] ?? null,
                'description' => $data['ar_description'] ?? null,
                'slug' => $data['ar_slug'] ?? null,
            ],
        ];
        $data['title'] = $this->translationsData['en']['title'] ?: 'Collection';
        $data['slug'] = $this->translationsData['en']['slug'] ?: Str::slug($data['title']);
        unset(
            $data['hadithItemIds'],
            $data['en_title'], $data['en_description'], $data['en_slug'],
            $data['ar_title'], $data['ar_description'], $data['ar_slug']
        );
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->translations()->updateOrCreate(
            ['locale' => 'en'],
            [
                'title' => $this->translationsData['en']['title'] ?: 'Collection',
                'description' => $this->translationsData['en']['description'],
                'slug' => $this->translationsData['en']['slug'],
            ]
        );
        if ($this->translationsData['ar']['title'] !== null && $this->translationsData['ar']['title'] !== '') {
            $this->record->translations()->updateOrCreate(
                ['locale' => 'ar'],
                [
                    'title' => $this->translationsData['ar']['title'],
                    'description' => $this->translationsData['ar']['description'],
                    'slug' => $this->translationsData['ar']['slug'],
                ]
            );
        }
        $this->record->syncHadithItems($this->hadithItemIds ?? []);
    }

    /** @var array<int> */
    private array $hadithItemIds = [];

    /** @var array<string, array{title: string|null, description: string|null, slug: string|null}> */
    private array $translationsData = [];
}
