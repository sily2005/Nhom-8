<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\MomoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoController extends Controller
{
    /**
     * Start / Initialize a MoMo payment link.
     *
     * @group Payment Management
     */
    public function start(Request $request, MomoService $momo): JsonResponse
    {
        Log::info('MomoController::start input:', [
            'all' => $request->all(),
            'json' => $request->json()->all(),
            'raw' => $request->getContent(),
        ]);

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'user_id' => ['sometimes', 'nullable', 'integer'],
            'order_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'order_number' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $orderId = (int) $validated['order_id'];
        $amount = (float) $validated['amount'];
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : 1;

        // 1. Create or update Payment record in striker_payment_db
        $payment = Payment::updateOrCreate(
            ['order_id' => $orderId],
            [
                'user_id' => $userId,
                'payment_method' => 'momo',
                'amount' => $amount,
                'status' => 'pending',
                'paid_at' => null,
            ]
        );

        // 2. Create PaymentTransaction record
        $transaction = PaymentTransaction::create([
            'payment_id' => $payment->id,
            'gateway' => 'momo',
            'amount' => $amount,
            'status' => 'pending',
        ]);

        // 3. Request MoMo payUrl
        $result = $momo->createPayment($payment, $transaction, [
            'order_code' => $validated['order_code'] ?? null,
            'order_number' => $validated['order_number'] ?? null,
        ]);

        if (isset($result['payUrl'])) {
            return response()->json([
                'success' => true,
                'message' => 'Tạo liên kết thanh toán MoMo thành công.',
                'data' => [
                    'pay_url' => $result['payUrl'],
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'momo_response' => $result,
                ],
                'errors' => null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Không thể khởi tạo liên kết thanh toán MoMo.',
            'data' => $result,
            'errors' => ['momo' => [$result['message'] ?? 'Lỗi kết nối cổng MoMo']],
        ], 422);
    }

    /**
     * Handle browser redirect callback from MoMo Gateway.
     *
     * @group Payment Management
     */
    public function callback(Request $request, MomoService $momo)
    {
        Log::info('MoMo Callback received in payment-service:', [
            'payload' => $request->except('signature'),
        ]);

        $resultCode = (int) $request->input('resultCode', -1);
        $isValid = $momo->isValidResponse($request->all());
        $isSuccessful = ($resultCode === 0) && ($isValid || app()->environment('local', 'testing'));

        if (!$isSuccessful) {
            Log::warning('MoMo Callback thất bại hoặc bị hủy:', [
                'result_code' => $resultCode,
                'order_id' => $request->input('orderId'),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giao dịch MoMo không thành công.',
                    'data' => $request->all(),
                ], 400);
            }

            return redirect('http://localhost:5173/orders?status=failed');
        }

        // Process completion and notify order-service
        $this->completePayment($request->all(), $momo);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán MoMo thành công!',
                'data' => $request->all(),
            ]);
        }

        return redirect('http://localhost:5173/orders?status=success');
    }

    /**
     * Handle Instant Payment Notification (IPN) webhook from MoMo Server.
     *
     * @group Payment Management
     */
    public function ipn(Request $request, MomoService $momo): JsonResponse
    {
        Log::info('MoMo IPN webhook received in payment-service:', [
            'payload' => $request->except('signature'),
        ]);

        $resultCode = (int) $request->input('resultCode', -1);
        $isValid = $momo->isValidResponse($request->all());
        $isSuccessful = ($resultCode === 0) && ($isValid || app()->environment('local', 'testing'));

        if ($isSuccessful) {
            $this->completePayment($request->all(), $momo);
        }

        return response()->json(['message' => 'Received']);
    }

    /**
     * Complete payment in DB and sync with order-service.
     */
    private function completePayment(array $payload, MomoService $momo): void
    {
        $gatewayOrderId = (string) ($payload['orderId'] ?? '');
        $extraData = (string) ($payload['extraData'] ?? '');

        // Find transaction by transaction_code or order_id
        $transaction = PaymentTransaction::where('gateway', 'momo')
            ->where('transaction_code', $gatewayOrderId)
            ->latest('id')
            ->first();

        if (!$transaction && !empty($extraData) && is_numeric($extraData)) {
            $payment = Payment::where('order_id', (int) $extraData)->first();
            if ($payment) {
                $transaction = PaymentTransaction::where('payment_id', $payment->id)
                    ->latest('id')
                    ->first();
            }
        }

        if (!$transaction && str_contains($gatewayOrderId, '_')) {
            $parts = explode('_', $gatewayOrderId);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $payment = Payment::where('order_id', (int) $parts[0])->first();
                if ($payment) {
                    $transaction = PaymentTransaction::where('payment_id', $payment->id)
                        ->latest('id')
                        ->first();
                }
            }
        }

        if (!$transaction) {
            Log::error('MoMo completePayment: Không tìm thấy transaction', ['payload' => $payload]);
            return;
        }

        $payment = $transaction->payment;
        if (!$payment) {
            Log::error('MoMo completePayment: Không tìm thấy payment', ['transaction' => $transaction]);
            return;
        }

        // 1. Mark Paid in striker_payment_db
        $momo->markPaid($payment, $transaction, $payload);

        // 2. Synchronize status with order-service (Port 8003)
        $this->notifyOrderServicePaid($payment->order_id, (string) ($payload['transId'] ?? ''));
    }

    /**
     * Call internal API in order-service to mark order as PAID.
     */
    private function notifyOrderServicePaid(int $orderId, string $transId): void
    {
        $orderServiceUrl = rtrim((string) config('services.microservices.order', env('ORDER_SERVICE_URL', 'http://127.0.0.1:8003')), '/');
        try {
            $response = Http::timeout(5)->post("{$orderServiceUrl}/api/orders/{$orderId}/mark-paid", [
                'payment_method' => 'momo',
                'transaction_id' => $transId,
                'secret' => env('INTERNAL_SERVICE_SECRET', 'STRIKER_SECRET_TOKEN_2026'),
            ]);

            Log::info("Đã đồng bộ trạng thái thanh toán MoMo sang order-service cho đơn hàng #{$orderId}", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi khi đồng bộ thanh toán sang order-service cho đơn #{$orderId}: " . $e->getMessage());
        }
    }
}
