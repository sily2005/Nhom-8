<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'order-service',
        'status' => 'running',
    ]);
});

Route::get('/payment/momo/callback', [\App\Http\Controllers\User\MomoController::class, 'callback'])->name('user.payment.momo.callback');
Route::post('/payment/momo/ipn', [\App\Http\Controllers\User\MomoController::class, 'ipn'])->name('payment.momo.ipn');
