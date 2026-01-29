<?php

namespace App\Http\Requests\QuranAllLang;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'unique:mysql_quran_all_lang.languages,code'],
            'name' => ['required', 'string', 'max:100'],
            'is_rtl' => ['boolean'],
        ];
    }
}
