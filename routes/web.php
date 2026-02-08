<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(Filament\Facades\Filament::getPanel('admin')->getUrl());
    }

    return redirect(Filament\Facades\Filament::getPanel('admin')->getLoginUrl());
});
