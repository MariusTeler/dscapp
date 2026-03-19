<?php

use App\Http\Controllers\Borderouri\BorderouController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth', 'verified'])->group(function () {
    // CRUD endpoints
    Route::get('slip', [BorderouController::class, 'index'])->name('slips.index');
    Route::get('slip/list', [BorderouController::class, 'data'])->name('slips.data');
    //Route::get('slip/create', [BorderouController::class, 'create'])->name('slips.create');
    Route::post('slip', [BorderouController::class, 'store'])->name('slips.store');
    Route::get('slip/{id}', [BorderouController::class, 'show'])->name('slips.show');
    Route::get('slip/{id}/awbs', [BorderouController::class, 'awbs'])->name('slips.awbs');
    Route::get('slip/{id}/pdf', [BorderouController::class, 'pdf'])->name('slips.pdf');
    Route::get('slip/{id}/awbs-pdf', [BorderouController::class, 'awbsPdf'])->name('slips.awbs-pdf');
    Route::get('slip/{id}/puisori-pdf', [BorderouController::class, 'awbsPuisoriPdf'])->name('slips.puisori-pdf');
    Route::get('slip/{id}/master-pdf', [BorderouController::class, 'awbsMasterPdf'])->name('slips.master-pdf');
    Route::get('slip/{id}/export', [BorderouController::class, 'export'])->name('slips.export');
    //Route::delete('slip/{id}', [BorderouController::class, 'destroy'])->name('slips.destroy');
});