<?php

use App\Http\Controllers\Comenzi\ComandaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth', 'verified'])->group(function () {
    Route::get('order', [ComandaController::class, 'index'])->name('orders.index');
    Route::get('order/list', [ComandaController::class, 'data'])->name('orders.data');
    Route::get('order/create', [ComandaController::class, 'create'])->name('orders.create');
    Route::post('order', [ComandaController::class, 'store'])->name('orders.store');
    Route::get('order/{id}', [ComandaController::class, 'show'])->name('orders.show');
    Route::get('order/{id}/edit', [ComandaController::class, 'edit'])->name('orders.edit');
    Route::put('order/{id}', [ComandaController::class, 'update'])->name('orders.update');
    Route::delete('order/{id}', [ComandaController::class, 'destroy'])->name('orders.destroy');
});
