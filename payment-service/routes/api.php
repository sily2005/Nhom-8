<?php

use App\Http\Controllers\MoMoPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Service API Routes (Port 8004)
|--------------------------------------------------------------------------
*/

// Khởi tạo thanh toán MoMo
Route::post('/payment/momo/start', [MoMoPaymentController::class, 'start']);
Route::post('/payment/momo/create', [MoMoPaymentController::class, 'start']);
Route::post('/payment/momo/ipn', [MoMoPaymentController::class, 'ipn']);

// Tra cứu trạng thái
Route::get('/payment/status', [MoMoPaymentController::class, 'status']);
Route::get('/payments/status', [MoMoPaymentController::class, 'status']);

// Báo cáo tài chính Admin (Finance)
Route::get('/admin/finance/summary', [MoMoPaymentController::class, 'financialReport']);
Route::get('/admin/finance/report', [MoMoPaymentController::class, 'financialReport']);
