<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(Filament\Facades\Filament::getPanel('admin')->getUrl());
    }

    return redirect(Filament\Facades\Filament::getPanel('admin')->getLoginUrl());
});

// Password reset links are deprecated. Reset is OTP-only via API/mobile.
Route::any('/reset-password', function () {
    abort(410, 'Password reset links are no longer supported.');
});
