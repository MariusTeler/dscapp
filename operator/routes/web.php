<?php

use App\Http\Controllers\ExpeditiiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/expeditii/cautare', [ExpeditiiController::class, 'cautare'])
        ->name('expeditii.cautare');

    Route::get('/expeditii/export', [ExpeditiiController::class, 'export'])
        ->name('expeditii.export');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

// Redirect root to cautare
Route::redirect('/', '/expeditii/cautare');
