<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/payments/settings', [PaymentController::class, 'getSettings']);
Route::put('/payments/settings', [PaymentController::class, 'updateSettings']);

Route::post('/payments', [PaymentController::class, 'processPayment']);
Route::get('/payments/status', [PaymentController::class, 'getPaymentStatus']);
Route::post('/payments/callback', [PaymentController::class, 'handleCallback']);