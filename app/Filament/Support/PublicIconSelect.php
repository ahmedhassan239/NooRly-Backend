<?php

namespace App\Filament\Support;

use App\Support\Icons\PublicIconsRegistry;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rule;

/**
 * Reusable Filament Select for icons under public/assets/icons.
 */
final class PublicIconSelect
{
    public static function make(
        string $name,
        string $label = 'Icon',
        bool $required = false,
        ?string $helperText = null,
    ): Select {
        $select = Select::make($name)
            ->label($label)
            ->options(fn (): array => PublicIconsRegistry::optionsForSelectWithImages())
            ->allowHtml()
            ->searchable()
            ->searchValues()
            ->preload()
            ->native(false)
            ->placeholder($required ? null : '— No icon —')
            ->formatStateUsing(function ($state): ?string {
                if ($state === null || $state === '') {
                    return null;
                }
                $s = is_string($state) ? $state : (string) $state;

                return PublicIconsRegistry::canonicalizeNullable($s) ?? $s;
            })
            ->dehydrateStateUsing(function ($state): ?string {
                if ($state === null || $state === '') {
                    return null;
                }

                return PublicIconsRegistry::canonicalizeNullable((string) $state);
            });

        if (! $required) {
            $select->nullable();
        } else {
            $select->required()->default(PublicIconsRegistry::defaultKey());
        }

        if ($helperText !== null) {
            $select->helperText($helperText);
        } else {
            $select->helperText('Icons from public/assets/icons. Stored value is a stable key (filename slug).');
        }

        $select->rules([
            $required ? 'required' : 'nullable',
            Rule::in(PublicIconsRegistry::allowedKeysForValidation()),
        ]);

        return $select;
    }
}
