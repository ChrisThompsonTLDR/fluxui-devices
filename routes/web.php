<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view(config('devices.device_route'), 'settings.devices')->name('devices.show');
});
