<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Stock verification & deduction APIs (called by order-service & frontend)
Route::post('/products/check-stock', [ProductController::class, 'checkStock']);
Route::post('/products/deduct-stock', [ProductController::class, 'deductStock']);
Route::post('/products/restore-stock', [ProductController::class, 'restoreStock']);

Route::apiResource('categories', CategoryController::class);
Route::apiResource('brands', BrandController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('banners', \App\Http\Controllers\BannerController::class);