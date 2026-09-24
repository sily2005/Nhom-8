<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

// Cart Routes
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']);
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);

// Order Routes
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/stats', [OrderController::class, 'stats']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{order}/ship-ghn', [OrderController::class, 'shipWithGHN']);

// Coupon / Voucher Routes
Route::get('/coupons', [CouponController::class, 'index']);
Route::post('/coupons', [CouponController::class, 'store']);
Route::get('/coupons/{coupon}', [CouponController::class, 'show']);
Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
Route::patch('/coupons/{coupon}', [CouponController::class, 'update']);
Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
Route::post('/coupons/apply', [CouponController::class, 'apply']);

// Review Routes
Route::get('/reviews', [ReviewController::class, 'index']);           // ?product_id=X
Route::post('/reviews', [ReviewController::class, 'store']);
Route::get('/reviews/check', [ReviewController::class, 'check']);     // ?order_id=X&product_id=Y&user_id=Z

// GHN Shipping Routes
Route::get('/shipping/provinces', [ShippingController::class, 'provinces']);
Route::get('/shipping/districts', [ShippingController::class, 'districts']);
Route::get('/shipping/wards', [ShippingController::class, 'wards']);
Route::post('/shipping/fee', [ShippingController::class, 'calculateFee']);
Route::post('/shipping/ghn/webhook', [ShippingController::class, 'webhook']);
Route::post('/ghn/webhook', [ShippingController::class, 'webhook']);

// MoMo Payment Routes
Route::post('/payment/momo/ipn', [\App\Http\Controllers\User\MomoController::class, 'ipn'])->name('api.payment.momo.ipn');
Route::get('/payment/momo/callback', [\App\Http\Controllers\User\MomoController::class, 'callback'])->name('api.payment.momo.callback');
Route::get('/orders/{order}/pay/momo', [\App\Http\Controllers\User\MomoController::class, 'payAgain'])->name('api.orders.momo.pay');
Route::get('/orders/{order}/start-momo', [\App\Http\Controllers\User\MomoController::class, 'start'])->name('api.orders.momo.start');