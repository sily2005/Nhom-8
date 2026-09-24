<?php

use App\Http\Controllers\MomoController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// MoMo Payment Gateway Routes
Route::post('/payment/momo/start', [MomoController::class, 'start']);
Route::get('/payment/momo/start', [MomoController::class, 'start']);
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// Backward-compatible alias routes
Route::get('/orders/{order}/start-momo', [MomoController::class, 'start']);
Route::get('/orders/{order}/pay/momo', [MomoController::class, 'start']);

// Generic Payment Transaction Status Route
Route::post('/payments', [PaymentController::class, 'processPayment']);
Route::get('/payments/status', [PaymentController::class, 'getPaymentStatus']);
Route::post('/payments/callback', [PaymentController::class, 'handleCallback']);