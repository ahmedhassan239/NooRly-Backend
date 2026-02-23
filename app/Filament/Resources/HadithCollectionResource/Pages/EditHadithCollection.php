<?php

namespace App\Filament\Resources\HadithCollectionResource\Pages;

use App\Filament\Resources\HadithCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditHadithCollection extends EditRecord
{
    protected static string $resource = HadithCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['hadithItemIds'] = $this->record->getHadithItemIds();
        $this->record->load('translations');
        foreach (['en', 'ar'] as $locale) {
            $t = $this->record->translations->firstWhere('locale', $locale);
            $data["{$locale}_title"] = $t?->title ?? ($locale === 'en' ? ($this->record->title ?? '') : null);
            $data["{$locale}_description"] = $t?->description;
            $data["{$locale}_slug"] = $t?->slug ?? ($locale === 'en' ? ($this->record->slug ?? null) : null);
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
        $data['title'] = $this->translationsData['en']['title'] ?: $this->record->title;
        $data['slug'] = $this->translationsData['en']['slug'] ?: $this->record->slug;
        unset(
            $data['hadithItemIds'],
            $data['en_title'], $data['en_description'], $data['en_slug'],
            $data['ar_title'], $data['ar_description'], $data['ar_slug']
        );
        return $data;
    }

    protected function afterSave(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $row = $this->translationsData[$locale];
            $this->record->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $row['title'] ?? ($locale === 'en' ? $this->record->title : ''),
                    'description' => $row['description'],
                    'slug' => $row['slug'],
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
