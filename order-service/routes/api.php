<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Service API Routes (Port 8003)
|--------------------------------------------------------------------------
*/

// Orders
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{order}/shipping', [OrderController::class, 'createGhnShipping']);
Route::post('/orders/{order}/mark-paid', [OrderController::class, 'markPaid']);

// Admin Orders
Route::get('/admin/orders', [AdminOrderController::class, 'index']);
Route::patch('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

// Cart
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'store']);
Route::put('/cart/{id}', [CartController::class, 'update']);
Route::delete('/cart/{id}', [CartController::class, 'destroy']);
Route::post('/cart/clear', [CartController::class, 'clear']);

// Coupons & Vouchers
Route::get('/coupons', [CouponController::class, 'index']);
Route::get('/vouchers', [CouponController::class, 'index']);
Route::post('/coupons/apply', [CouponController::class, 'apply']);
Route::post('/coupons', [CouponController::class, 'store']);
Route::put('/coupons/{id}', [CouponController::class, 'update']);
Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);
Route::patch('/coupons/{id}/toggle', [CouponController::class, 'toggleActive']);

// Shipping & GHN Logistics
Route::post('/shipping/fee', [ShippingController::class, 'calculateFee']);
Route::get('/shipping/provinces', [ShippingController::class, 'getProvinces']);
Route::get('/shipping/districts', [ShippingController::class, 'getDistricts']);
Route::get('/shipping/wards', [ShippingController::class, 'getWards']);

// Reviews
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/summary', [ReviewController::class, 'summary']);
Route::get('/reviews/check', [ReviewController::class, 'check']);
Route::post('/reviews', [ReviewController::class, 'store']);
