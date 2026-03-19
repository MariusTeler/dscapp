<?php

use App\Http\Controllers\Commons\LocalitatiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth'])->group(function () {
    // Localitati autocomplete
    Route::get('commons/localitati', [LocalitatiController::class, 'autocomplete'])->name('commons.localitati.autocomplete');
});