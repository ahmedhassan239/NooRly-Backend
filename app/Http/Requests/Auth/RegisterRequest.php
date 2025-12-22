<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentUser = $this->user();
        $emailRule = ['required', 'string', 'email', 'max:255'];

        if ($currentUser && $currentUser->is_guest) {
            $emailRule[] = 'unique:app_users,email,'.$currentUser->id;
        } else {
            $emailRule[] = 'unique:app_users,email';
        }

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => $emailRule,
            'password' => ['required', 'string', 'min:8'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'shahada_date' => ['nullable', 'date'],
            'main_goal' => ['nullable', 'string', 'in:salah,quran_basics,faith_essentials,exploring'],
            'timezone' => ['required', 'string', 'timezone'],
            'country' => ['nullable', 'string', 'max:255'],
        ];
    }
}
