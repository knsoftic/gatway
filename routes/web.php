<?php

use App\Http\Controllers\PayfastController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('payfast.create');
});

// PayFast QR Pay Routes
Route::prefix('payfast')->name('payfast.')->group(function () {
    Route::get('/settings', [PayfastController::class, 'settings'])->name('settings');
    Route::post('/settings', [PayfastController::class, 'saveSettings'])->name('settings.save');
    Route::post('/settings/test', [PayfastController::class, 'testConnection'])->name('settings.test');
    Route::get('/create', [PayfastController::class, 'create'])->name('create');
    Route::post('/create', [PayfastController::class, 'store'])->name('store');
    Route::get('/qr/{qrToken}', [PayfastController::class, 'showQr'])->name('qr.show');
    Route::get('/pay/{qrToken}', [PayfastController::class, 'payForm'])->name('pay');
    Route::post('/pay/{qrToken}/validate', [PayfastController::class, 'validateCustomer'])->name('validate');
    Route::post('/pay/{qrToken}/process', [PayfastController::class, 'processPayment'])->name('process');
    Route::get('/success/{qrToken}', [PayfastController::class, 'success'])->name('success');
    Route::get('/failed/{qrToken}', [PayfastController::class, 'failed'])->name('failed');
    Route::get('/status/{qrToken}', [PayfastController::class, 'checkStatus'])->name('status');
});
