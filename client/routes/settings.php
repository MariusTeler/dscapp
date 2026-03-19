<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\OptionsController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\UpdateEmailController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rute pentru actualizare email lipsa - doar cu auth, fara verified
Route::middleware(['web','auth'])->group(function () {
    Route::get('email/missing', [UpdateEmailController::class, 'show'])->name('email.missing');
    Route::post('email/update', [UpdateEmailController::class, 'update'])->name('email.update');
});

Route::middleware(['web','auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    //Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/options', [OptionsController::class, 'edit'])->name('options.edit');
    Route::patch('settings/options', [OptionsController::class, 'update'])->name('options.update');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
