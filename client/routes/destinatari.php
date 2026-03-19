<?php

use App\Http\Controllers\Destinatari\DestinatarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth', 'verified'])->group(function () {
    // Individual destinatar routes
    Route::get('recipient', [DestinatarController::class, 'index'])->name('recipients.index');
    Route::get('recipient/autocomplete', [DestinatarController::class, 'autocomplete'])->name('recipients.autocomplete');
    Route::get('recipient/list', [DestinatarController::class, 'data'])->name('recipients.data');
    Route::get('recipient/import/template', [DestinatarController::class, 'downloadTemplate'])->name('recipients.import.template');
    Route::post('recipient/import/validate', [DestinatarController::class, 'validateImport'])->name('recipients.import.validate');
    Route::post('recipient/import/run', [DestinatarController::class, 'runImport'])->name('recipients.import.run');

    Route::get('recipient/create', [DestinatarController::class, 'create'])->name('recipients.create');
    Route::post('recipient', [DestinatarController::class, 'store'])->name('recipients.store');
    Route::get('recipient/{id}', [DestinatarController::class, 'show'])->name('recipients.show');
    Route::get('recipient/{id}/edit', [DestinatarController::class, 'edit'])->name('recipients.edit');
    Route::put('recipient/{id}', [DestinatarController::class, 'update'])->name('recipients.update');
    Route::delete('recipient/{id}', [DestinatarController::class, 'destroy'])->name('recipients.destroy');
});
