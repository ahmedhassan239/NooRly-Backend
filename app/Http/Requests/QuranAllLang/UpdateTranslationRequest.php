<?php

namespace App\Http\Requests\QuranAllLang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language_id' => ['sometimes', 'integer', 'exists:mysql_quran_all_lang.languages,id'],
            'source_name' => ['sometimes', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
