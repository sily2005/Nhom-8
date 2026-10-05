<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoMoPaymentController extends Controller
{
    /**
     * Khởi tạo liên kết thanh toán MoMo Sandbox / QR Code
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'order_code' => ['nullable', 'string'],
            'user_id' => ['nullable'],
        ]);

        $rawOrderId = $validated['order_id'];
        $amount = (float) $validated['amount'];
        $orderCode = $validated['order_code'] ?? (is_numeric($rawOrderId) ? 'ORD-' . $rawOrderId : (string) $rawOrderId);

        // Chuẩn hóa order_id sang số hoặc hash để lưu vào DB
        $numericOrderId = is_numeric($rawOrderId) ? (int) $rawOrderId : (abs(crc32((string) $rawOrderId)) % 1000000000);

        // Lưu / cập nhật bản ghi Payment
        $payment = Payment::updateOrCreate(
            ['order_id' => $numericOrderId],
            [
                'payment_method' => 'momo',
                'amount' => $amount,
                'status' => 'pending',
            ]
        );

        // Tạo mã giao dịch MoMo
        $requestId = (string) Str::uuid();
        $momoOrderId = $orderCode . '_' . time();

        // Cấu hình URL trả về cho Frontend
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $redirectUrl = "{$frontendUrl}/payment/callback?orderId={$numericOrderId}&orderCode={$orderCode}&amount={$amount}&partnerCode=MOMO&resultCode=0&message=Success";

        // URL MoMo thực tế hoặc Sandbox Mock link
        $payUrl = $redirectUrl;

        // Lưu Transaction Log
        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'gateway' => 'momo',
            'transaction_code' => $momoOrderId,
            'response_code' => '0',
            'amount' => $amount,
            'status' => 'initiated',
            'raw_payload' => [
                'requestId' => $requestId,
                'orderId' => $momoOrderId,
                'orderCode' => $orderCode,
                'amount' => $amount,
                'payUrl' => $payUrl,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Khởi tạo cổng thanh toán MoMo thành công.',
            'data' => [
                'pay_url' => $payUrl,
                'order_id' => $numericOrderId,
                'order_code' => $orderCode,
                'amount' => $amount,
                'payment_id' => $payment->id,
            ],
        ]);
    }

    /**
     * Webhook IPN xử lý kết quả thanh toán từ MoMo
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['orderId'] ?? null;
        $resultCode = $payload['resultCode'] ?? null;

        if ($resultCode == 0 && $orderId) {
            $payment = Payment::where('order_id', $orderId)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Bắn thông báo sang Order Service để cập nhật trạng thái đơn hàng
                try {
                    $orderServiceUrl = env('ORDER_SERVICE_URL', 'http://127.0.0.1:8003');
                    Http::timeout(5)->post("{$orderServiceUrl}/api/orders/{$orderId}/mark-paid");
                } catch (\Exception $e) {
                    Log::warning('Không thể thông báo mark-paid sang Order Service: ' . $e->getMessage());
                }
            }
        }

        return response()->json(['message' => 'IPN received successfully', 'status' => 'OK']);
    }

    /**
     * Tra cứu trạng thái thanh toán theo order_id
     */
    public function status(Request $request): JsonResponse
    {
        $orderId = $request->query('order_id');
        if (!$orderId) {
            return response()->json(['success' => false, 'message' => 'Thiếu order_id'], 400);
        }

        $numericOrderId = is_numeric($orderId) ? (int) $orderId : (abs(crc32((string) $orderId)) % 1000000000);
        $payment = Payment::where('order_id', $numericOrderId)->with('transactions')->first();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Báo cáo Tài chính & Doanh thu Admin
     */
    public function financialReport(Request $request): JsonResponse
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $momoRevenue = Payment::where('status', 'paid')->where('payment_method', 'momo')->sum('amount');
        $codRevenue = Payment::where('status', 'paid')->where('payment_method', 'cod')->sum('amount');
        $transactions = PaymentTransaction::latest()->take(50)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (float) $totalRevenue,
                'momo_revenue' => (float) $momoRevenue,
                'cod_revenue' => (float) $codRevenue,
                'transactions' => $transactions,
            ],
        ]);
    }
}
