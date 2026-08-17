<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

// Public pages. Light throttling keeps a scraper or a hammered QR code from
// filling qr_scans, without getting in a real diner's way.
Route::middleware('throttle:public')->group(function () {
    Route::get('/', [MenuController::class, 'landing'])
        ->middleware('qr.scan')
        ->name('landing');

    Route::get('/menu', [MenuController::class, 'menu'])->name('menu');
});
