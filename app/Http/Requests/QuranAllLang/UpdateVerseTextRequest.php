<?php

namespace App\Http\Requests\QuranAllLang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerseTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
        ];
    }
}
