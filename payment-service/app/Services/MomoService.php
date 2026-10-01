<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoService
{
    /**
     * Create a payment transaction with MoMo Gateway.
     */
    public function createPayment(Payment $payment, PaymentTransaction $transaction, array $options = []): array
    {
        $endpoint = config('services.momo.endpoint', env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'));
        $partnerCode = config('services.momo.partner_code', env('MOMO_PARTNER_CODE', 'MOMOBKUN20180529'));
        $accessKey = config('services.momo.access_key', env('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j'));
        $secretKey = config('services.momo.secret_key', env('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'));

        $orderIdNumeric = $payment->order_id;
        $orderCode = $options['order_code'] ?? $options['order_number'] ?? ('#' . $orderIdNumeric);
        $orderInfo = 'Thanh toan don hang ' . $orderCode . ' tai STRIKER Cyber-Sport';
        $amount = (string) ((int) $payment->amount);
        
        // MoMo unique orderId: <order_id>_<payment_id>_<transaction_id>_<timestamp>
        $gatewayOrderId = $orderIdNumeric . '_' . $transaction->id . '_' . time();
        $redirectUrl = config('services.momo.redirect_url') ?: env('MOMO_REDIRECT_URL', 'http://localhost:8000/payment/momo/callback');
        $ipnUrl = config('services.momo.ipn_url') ?: env('MOMO_IPN_URL', 'http://localhost:8000/payment/momo/ipn');
        $extraData = (string) $orderIdNumeric;
        $requestId = (string) time();
        $requestType = config('services.momo.request_type', env('MOMO_REQUEST_TYPE', 'payWithATM'));

        $rawHash = 'accessKey=' . $accessKey .
            '&amount=' . $amount .
            '&extraData=' . $extraData .
            '&ipnUrl=' . $ipnUrl .
            '&orderId=' . $gatewayOrderId .
            '&orderInfo=' . $orderInfo .
            '&partnerCode=' . $partnerCode .
            '&redirectUrl=' . $redirectUrl .
            '&requestId=' . $requestId .
            '&requestType=' . $requestType;

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'STRIKER Sport',
            'storeId' => 'StrikerStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $gatewayOrderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => hash_hmac('sha256', $rawHash, $secretKey),
        ];

        $transaction->update([
            'transaction_code' => $gatewayOrderId,
            'raw_payload' => array_merge($transaction->raw_payload ?? [], ['request' => $data]),
        ]);

        try {
            $verifySsl = filter_var(config('services.momo.verify_ssl', env('MOMO_VERIFY_SSL', false)), FILTER_VALIDATE_BOOLEAN);
            $response = Http::timeout(6)->withOptions(['verify' => $verifySsl])->post($endpoint, $data);
            $result = $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Lỗi gọi API MoMo Gateway:', ['error' => $e->getMessage()]);
            $result = [
                'resultCode' => 99,
                'message' => 'Lỗi kết nối tới cổng MoMo: ' . $e->getMessage(),
            ];
        }

        $resultCode = isset($result['resultCode']) ? (int) $result['resultCode'] : -1;
        $transaction->update([
            'response_code' => (string) $resultCode,
            'status' => isset($result['payUrl']) ? 'initiated' : 'failed',
            'raw_payload' => array_merge($transaction->raw_payload ?? [], ['response' => $result]),
        ]);

        return $result;
    }

    /**
     * Validate HMAC-SHA256 signature from MoMo response/IPN.
     */
    public function isValidResponse(array $payload): bool
    {
        if (!isset($payload['signature'])) {
            return false;
        }

        $accessKey = config('services.momo.access_key', env('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j'));
        $secretKey = config('services.momo.secret_key', env('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'));

        $rawHash = 'accessKey=' . $accessKey .
            '&amount=' . ($payload['amount'] ?? '') .
            '&extraData=' . ($payload['extraData'] ?? '') .
            '&message=' . ($payload['message'] ?? '') .
            '&orderId=' . ($payload['orderId'] ?? '') .
            '&orderInfo=' . ($payload['orderInfo'] ?? '') .
            '&orderType=' . ($payload['orderType'] ?? '') .
            '&partnerCode=' . ($payload['partnerCode'] ?? '') .
            '&payType=' . ($payload['payType'] ?? '') .
            '&requestId=' . ($payload['requestId'] ?? '') .
            '&responseTime=' . ($payload['responseTime'] ?? '') .
            '&resultCode=' . ($payload['resultCode'] ?? '') .
            '&transId=' . ($payload['transId'] ?? '');

        return hash_equals(
            hash_hmac('sha256', $rawHash, $secretKey),
            (string) $payload['signature']
        );
    }

    /**
     * Mark transaction and payment as paid.
     */
    public function markPaid(Payment $payment, PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'response_code' => (string) ($payload['resultCode'] ?? '0'),
            'status' => 'paid',
            'raw_payload' => array_merge($transaction->raw_payload ?? [], ['ipn_callback' => $payload]),
        ]);

        $payment->update([
            'transaction_id' => (string) ($payload['transId'] ?? $transaction->transaction_code),
            'status' => 'completed',
            'paid_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark transaction and payment as failed.
     */
    public function markFailed(Payment $payment, PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'response_code' => (string) ($payload['resultCode'] ?? '99'),
            'status' => 'failed',
            'raw_payload' => array_merge($transaction->raw_payload ?? [], ['failed_payload' => $payload]),
        ]);

        $payment->update([
            'status' => 'failed',
        ]);
    }
}
