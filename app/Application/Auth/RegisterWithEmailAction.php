<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use Illuminate\Support\Facades\Hash;

class RegisterWithEmailAction
{
    public function execute(?AppUser $currentUser, array $data): AppUser
    {
        if ($currentUser && $currentUser->is_guest) {
            return $this->upgradeGuestUser($currentUser, $data);
        }

        return $this->createNewUser($data);
    }

    private function upgradeGuestUser(AppUser $guestUser, array $data): AppUser
    {
        $guestUser->update([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'name' => $data['name'] ?? $guestUser->name,
            'gender' => $data['gender'] ?? $guestUser->gender,
            'date_of_birth' => $data['date_of_birth'] ?? $guestUser->date_of_birth,
            'shahada_date' => $data['shahada_date'] ?? $guestUser->shahada_date,
            'main_goal' => $data['main_goal'] ?? $guestUser->main_goal,
            'timezone' => $data['timezone'] ?? $guestUser->timezone,
            'country' => $data['country'] ?? $guestUser->country,
            'is_guest' => false,
        ]);

        return $guestUser->fresh();
    }

    private function createNewUser(array $data): AppUser
    {
        return AppUser::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'name' => $data['name'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'shahada_date' => $data['shahada_date'] ?? null,
            'main_goal' => $data['main_goal'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'country' => $data['country'] ?? null,
            'is_guest' => false,
        ]);
    }
}
