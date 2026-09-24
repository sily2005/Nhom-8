<?php

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/payment/momo/callback', function (Illuminate\Http\Request $request) {
    $orderService = rtrim((string) config('services.microservices.order', 'http://127.0.0.1:8003'), '/');

    // 1. Gọi sang Order Service để ghi nhận trạng thái thanh toán đơn hàng vào Database
    try {
        Http::get("{$orderService}/api/payment/momo/callback", $request->query());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Lỗi gọi callback order-service từ api:', ['error' => $e->getMessage()]);
    }

    $status = (string) $request->input('resultCode', '0') === '0' ? 'success' : 'failed';
    // 2. Trả về chuyển hướng trực tiếp cho trình duyệt về trang đơn hàng Frontend React
    return redirect("http://localhost:5173/orders?status={$status}");
});

Route::post('/payment/momo/ipn', function (Illuminate\Http\Request $request) {
    $orderService = rtrim((string) config('services.microservices.order', 'http://127.0.0.1:8003'), '/');
    try {
        $response = Http::post("{$orderService}/api/payment/momo/ipn", $request->all());
        return response($response->body(), $response->status(), $response->headers());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Lỗi gọi IPN order-service từ api:', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Internal Error'], 500);
    }
});

Route::any('/auth/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');
Route::any('/addresses/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');
Route::any('/users/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');
Route::any('/chat/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');
Route::any('/user/chat/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');
Route::any('/admin/chat/{any?}', [GatewayController::class, 'auth'])->where('any', '.*');

// Chặn truy cập công khai vào endpoint nội bộ của Catalog Service (SEC-02)
Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '/products/deduct-stock', function () {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'FORBIDDEN',
            'message' => 'Endpoint nội bộ chỉ dành cho giao tiếp giữa các microservices.',
        ],
    ], 403);
});

Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '/products/restore-stock', function () {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'FORBIDDEN',
            'message' => 'Endpoint nội bộ chỉ dành cho giao tiếp giữa các microservices.',
        ],
    ], 403);
});

Route::any('/categories/{any?}', [GatewayController::class, 'catalog'])->where('any', '.*');
Route::any('/products/{any?}', [GatewayController::class, 'catalog'])->where('any', '.*');

Route::any('/brands/{any?}', [GatewayController::class, 'catalog'])->where('any', '.*');
Route::any('/banners/{any?}', [GatewayController::class, 'catalog'])->where('any', '.*');
Route::any('/cart/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
Route::any('/orders/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
Route::any('/coupons/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
Route::any('/reviews/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
Route::any('/shipping/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
Route::any('/payments/{any?}', [GatewayController::class, 'payment'])->where('any', '.*');
Route::any('/payment/{any?}', [GatewayController::class, 'order'])->where('any', '.*');
