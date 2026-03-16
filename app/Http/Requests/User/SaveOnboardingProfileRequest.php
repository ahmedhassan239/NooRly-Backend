<?php

namespace App\Http\Requests\User;

use App\Domain\Users\UserOnboardingProfile;
use Illuminate\Foundation\Http\FormRequest;

class SaveOnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $embrace = implode(',', UserOnboardingProfile::EMBRACE_ISLAM_RANGE);
        $arabic = implode(',', UserOnboardingProfile::ARABIC_LEVEL);
        $prayer = implode(',', UserOnboardingProfile::PRAYER_LEVEL);
        $quran = implode(',', UserOnboardingProfile::QURAN_READING_LEVEL);
        $goals = implode(',', UserOnboardingProfile::GOALS);
        $challenges = implode(',', UserOnboardingProfile::CHALLENGES);
        $dailyTime = implode(',', UserOnboardingProfile::DAILY_TIME);
        $learningTime = implode(',', UserOnboardingProfile::PREFERRED_LEARNING_TIME);
        $learningStyle = implode(',', UserOnboardingProfile::LEARNING_STYLE);
        $reminder = implode(',', UserOnboardingProfile::REMINDER_PREFERENCE);

        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'embrace_islam_range' => ['nullable', "in:{$embrace}"],
            'arabic_level' => ['nullable', "in:{$arabic}"],
            'prayer_level' => ['nullable', "in:{$prayer}"],
            'quran_reading_level' => ['nullable', "in:{$quran}"],
            'goals' => ['nullable', 'array'],
            'goals.*' => ['string', "in:{$goals}"],
            'challenges' => ['nullable', 'array'],
            'challenges.*' => ['string', "in:{$challenges}"],
            'daily_time' => ['nullable', "in:{$dailyTime}"],
            'preferred_learning_time' => ['nullable', "in:{$learningTime}"],
            'learning_style' => ['nullable', "in:{$learningStyle}"],
            'reminder_preference' => ['nullable', "in:{$reminder}"],
            'islam_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
