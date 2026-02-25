<?php

namespace App\Http\Requests\Adhkar;

use App\Rules\CategoryExistsInScopeRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Adhkar request: accepts only category_id (required, must belong to adhkar scope).
 * Legacy fields (category, content_category, content_category_id) are not accepted.
 */
class StoreAdhkarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                new CategoryExistsInScopeRule('adhkar'),
            ],
            'text' => ['sometimes', 'array'],
            'text.en' => ['sometimes', 'string'],
            'text.ar' => ['sometimes', 'string'],
            'reward' => ['sometimes', 'nullable', 'array'],
            'count' => ['sometimes', 'integer', 'min:1'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /** Legacy keys that must not be accepted. */
    private const LEGACY_CATEGORY_KEYS = ['category', 'content_category', 'content_category_id'];

    /**
     * Strip legacy category fields so they are never used.
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach (self::LEGACY_CATEGORY_KEYS as $key) {
            unset($input[$key]);
        }
        $this->replace($input);
    }
}
