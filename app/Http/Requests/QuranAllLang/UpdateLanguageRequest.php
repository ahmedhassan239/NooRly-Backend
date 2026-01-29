<?php

namespace App\Http\Requests\QuranAllLang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:10', 'unique:mysql_quran_all_lang.languages,code,' . $this->route('id')],
            'name' => ['sometimes', 'string', 'max:100'],
            'is_rtl' => ['boolean'],
        ];
    }
}
