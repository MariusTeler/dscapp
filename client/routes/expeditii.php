<?php

use App\Http\Controllers\Expeditii\AwbController;
use App\Http\Controllers\Expeditii\ListeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth', 'verified'])->group(function () {
    // AWB routes
    Route::post('awb/price', [AwbController::class, 'price'])->name('awb.price');
    Route::post('awb/import/validate', [AwbController::class, 'validateImport'])->name('awb.import.validate');
    Route::post('awb/import/run', [AwbController::class, 'runImport'])->name('awb.import.run');
    Route::get('awb/import/templateCsv', [AwbController::class, 'downloadTemplateCsv'])->name('awb.import.templateCsv');
    Route::get('awb/import/templateXls', [AwbController::class, 'downloadTemplateXls'])->name('awb.import.templateXls');
    Route::get('awb/pdf', [AwbController::class, 'awbPdf'])->name('awb.pdf');
    Route::get('awb/puisori-pdf', [AwbController::class, 'awbPuisoriPdf'])->name('awb.puisori-pdf');
    Route::get('awb/master-pdf', [AwbController::class, 'awbMasterPdf'])->name('awb.master-pdf');
    
    Route::get('awb', [AwbController::class, 'index'])->name('awb.index');
    Route::get('awb/create', [AwbController::class, 'create'])->name('awb.create');
    Route::post('awb', [AwbController::class, 'store'])->name('awb.store');
    Route::get('awb/{awb}', [AwbController::class, 'show'])->name('awb.show');
    Route::get('awb/{awb}/edit', [AwbController::class, 'edit'])->name('awb.edit');
    Route::put('awb/{awb}', [AwbController::class, 'update'])->name('awb.update');
    Route::delete('awb/{awb}', [AwbController::class, 'destroy'])->name('awb.destroy');

    // AWB Liste routes moved to shipments
    Route::get('shipments', [ListeController::class, 'index'])->name('shipments.index');
    Route::get('shipments/nepredate', [ListeController::class, 'nepredate'])->name('shipments.nepredate');
    Route::get('shipments/predate', [ListeController::class, 'predate'])->name('shipments.predate');
    Route::get('shipments/predate/export', [ListeController::class, 'exportPredate'])->name('shipments.predate.export');
    Route::get('shipments/retururi', [ListeController::class, 'retururi'])->name('shipments.retururi');
    Route::get('shipments/retururi/export', [ListeController::class, 'exportRetururi'])->name('shipments.retururi.export');
    Route::get('shipments/{tab}/stats', [ListeController::class, 'stats'])
        ->where('tab', 'nepredate|predate|retururi')
        ->name('shipments.stats');
});
