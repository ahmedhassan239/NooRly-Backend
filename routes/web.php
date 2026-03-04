<?php

use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(Filament\Facades\Filament::getPanel('admin')->getUrl());
    }

    return redirect(Filament\Facades\Filament::getPanel('admin')->getLoginUrl());
});

// Public password reset page (for email link: https://noorly.net/reset-password?token=...&email=...)
Route::get('/reset-password', function (Request $request) {
    $token = $request->query('token');
    $email = $request->query('email');

    return response()->view('auth.reset-password', [
        'token' => $token ?? '',
        'email' => $email ?? '',
        'error' => session('reset_error'),
        'success' => session('reset_success'),
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request, PasswordResetService $passwordReset) {
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|max:255',
        'token' => 'required|string',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return redirect()->route('password.reset')
            ->withInput($request->only('email', 'token'))
            ->withErrors($validator)
            ->with('reset_error', 'Please fix the errors below.');
    }

    try {
        $passwordReset->reset(
            $request->email,
            $request->token,
            $request->password
        );

        return redirect()->route('password.reset')
            ->with('reset_success', 'Your password has been reset. You can now log in.');
    } catch (\Exception $e) {
        return redirect()->route('password.reset')
            ->withInput($request->only('email', 'token'))
            ->with('reset_error', $e->getMessage());
    }
})->name('password.reset.submit');
