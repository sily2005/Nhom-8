<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\GhnService;
use App\Services\MomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MomoController extends Controller
{
    public function start($orderParam, MomoService $momo)
    {
        $order = $orderParam instanceof Order
            ? $orderParam
            : Order::where('id', $orderParam)
                ->orWhere('order_number', $orderParam)
                ->orWhere('order_code', $orderParam)
                ->firstOrFail();

        return $this->redirectToMomo($order, $this->newTransaction($order), $momo);
    }

    public function payAgain($orderParam, MomoService $momo)
    {
        $order = $orderParam instanceof Order
            ? $orderParam
            : Order::where('id', $orderParam)
                ->orWhere('order_number', $orderParam)
                ->orWhere('order_code', $orderParam)
                ->firstOrFail();

        return $this->redirectToMomo($order, $this->newTransaction($order), $momo);
    }

    public function callback(Request $request, GhnService $ghnOrders, MomoService $momo)
    {
        Log::info('MoMo callback received', [
            'payload' => $request->except('signature'),
            'has_signature' => $request->has('signature'),
        ]);

        $resultCode = (int) $request->input('resultCode', -1);
        $isSuccessful = ($resultCode === 0) && $momo->isValidResponse($request->all());

        if (!$isSuccessful) {
            Log::warning('MoMo callback rejected or failed', [
                'result_code' => $request->input('resultCode'),
                'order_id' => $request->input('orderId'),
                'signature_valid' => $momo->isValidResponse($request->all()),
            ]);

            if ($momo->isValidResponse($request->all())) {
                $this->markFailed($request->all(), $momo);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giao dịch MoMo thất bại hoặc đã bị hủy.',
                    'data' => $request->all(),
                ], 400);
            }

            return redirect('http://localhost:5173/orders?status=failed');
        }

        $this->completePayment($request->all(), $ghnOrders, $momo);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán MoMo thành công!',
                'data' => $request->all(),
            ]);
        }

        return redirect('http://localhost:5173/orders?status=success');
    }

    public function ipn(Request $request, GhnService $ghnOrders, MomoService $momo)
    {
        Log::info('MoMo IPN received', [
            'payload' => $request->except('signature'),
            'has_signature' => $request->has('signature'),
        ]);

        if ($momo->isValidSuccessfulResponse($request->all())) {
            $this->completePayment($request->all(), $ghnOrders, $momo);
        } elseif ($momo->isValidResponse($request->all())) {
            $this->markFailed($request->all(), $momo);
        }

        return response()->json(['message' => 'Received']);
    }

    private function newTransaction(Order $order): PaymentTransaction
    {
        return PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'momo',
            'amount' => (float) ($order->total_amount ?? $order->total_price ?? 0),
            'status' => 'pending',
        ]);
    }

    private function redirectToMomo(Order $order, PaymentTransaction $transaction, MomoService $momo)
    {
        $result = $momo->createPayment($order, $transaction);

        if (request()->wantsJson() || request()->is('api/*') || request()->ajax() || request()->header('Accept') === 'application/json') {
            if (isset($result['payUrl'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo liên kết thanh toán MoMo thành công.',
                    'data' => [
                        'pay_url' => $result['payUrl'],
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_code' => $order->order_code,
                        'transaction_id' => $transaction->id,
                        'momo_response' => $result,
                    ],
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Không thể kết nối tới MoMo.',
                'data' => $result,
            ], 422);
        }

        return isset($result['payUrl'])
            ? redirect($result['payUrl'])
            : redirect('http://localhost:5173/orders?status=failed');
    }

    private function completePayment(array $payload, GhnService $ghnOrders, MomoService $momo): string
    {
        $result = DB::transaction(function () use ($payload, $momo) {
            $transaction = PaymentTransaction::where('gateway', 'momo')
                ->where('gateway_order_id', $payload['orderId'] ?? '')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                return 'invalid';
            }

            $order = Order::lockForUpdate()->find($transaction->order_id);
            if (!$order) {
                return 'invalid';
            }

            if ($order->ghn_code || ($order->ghn_order_code ?? null)) {
                return 'already_created';
            }

            if ($order->order_status === 'paid' || $order->payment_status === 'paid') {
                return 'already_paid';
            }

            if ((int) $transaction->amount !== (int) ($payload['amount'] ?? 0)) {
                $momo->markFailed($transaction, $payload);
                return 'invalid';
            }

            $order->update([
                'status' => 'processing',
                'order_status' => 'processing',
                'payment_status' => 'paid',
            ]);

            $momo->markPaid($transaction, $payload);

            return ['create', $order->id];
        });

        if (!is_array($result)) {
            return (string) $result;
        }

        $order = Order::with('items')->find($result[1]);
        try {
            $response = $ghnOrders->createShippingOrder($order);
            if (!empty($response['order_code'])) {
                $order->update([
                    'ghn_code' => $response['order_code'],
                    'order_status' => 'shipping',
                    'status' => 'shipping',
                ]);
                return 'created';
            }
        } catch (\Exception $e) {
            Log::error('GHN order failed after MoMo payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return 'failed';
    }

    private function markFailed(array $payload, MomoService $momo): void
    {
        $transaction = PaymentTransaction::where('gateway', 'momo')
            ->where('gateway_order_id', $payload['orderId'] ?? '')
            ->first();

        if ($transaction && $transaction->status !== 'paid') {
            $momo->markFailed($transaction, $payload);
        }
    }
}
