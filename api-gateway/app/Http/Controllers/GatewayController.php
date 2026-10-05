<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    private array $services;

    public function __construct()
    {
        $this->services = [
            'auth' => rtrim(env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
            'catalog' => rtrim(env('CATALOG_SERVICE_URL', 'http://127.0.0.1:8002'), '/'),
            'order' => rtrim(env('ORDER_SERVICE_URL', 'http://127.0.0.1:8003'), '/'),
            'payment' => rtrim(env('PAYMENT_SERVICE_URL', 'http://127.0.0.1:8004'), '/'),
        ];
    }

    /**
     * Dispatch request to appropriate microservice based on URI pattern
     */
    public function handle(Request $request, string $path = '')
    {
        $targetService = $this->resolveService($path);
        
        if (!$targetService) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy dịch vụ phù hợp cho đường dẫn: /api/{$path}",
            ], 404);
        }

        $serviceUrl = $this->services[$targetService];
        $targetUrl = "{$serviceUrl}/api/" . ltrim($path, '/');

        // Extract headers to forward
        $headers = [];
        if ($authHeader = $request->header('Authorization')) {
            $headers['Authorization'] = $authHeader;
        }
        $headers['Accept'] = 'application/json';
        $headers['X-Forwarded-For'] = $request->ip();

        $method = strtoupper($request->method());
        $queryParams = $request->query();

        try {
            $httpClient = Http::withHeaders($headers)->timeout(12);

            if ($request->isJson() || $request->hasHeader('Content-Type') && str_contains($request->header('Content-Type'), 'application/json')) {
                $payload = $request->json()->all();
            } else {
                $payload = $request->all();
            }

            $response = match ($method) {
                'GET' => $httpClient->get($targetUrl, $queryParams),
                'POST' => $httpClient->post($targetUrl . (!empty($queryParams) ? '?' . http_build_query($queryParams) : ''), $payload),
                'PUT' => $httpClient->put($targetUrl . (!empty($queryParams) ? '?' . http_build_query($queryParams) : ''), $payload),
                'PATCH' => $httpClient->patch($targetUrl . (!empty($queryParams) ? '?' . http_build_query($queryParams) : ''), $payload),
                'DELETE' => $httpClient->delete($targetUrl . (!empty($queryParams) ? '?' . http_build_query($queryParams) : ''), $payload),
                default => abort(405, 'Phương thức HTTP không được hỗ trợ.'),
            };

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?: 'application/json');
        } catch (\Exception $e) {
            Log::error("Gateway Proxy Error [{$targetService}]: " . $e->getMessage(), [
                'target' => $targetUrl,
                'method' => $method,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Không thể kết nối đến dịch vụ [{$targetService}]. Vui lòng đảm bảo dịch vụ đang chạy trên cổng tương ứng.",
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Map request path to backend service key
     */
    private function resolveService(string $path): ?string
    {
        $path = ltrim($path, '/');

        // Auth Service (Port 8001)
        if (
            str_starts_with($path, 'auth') ||
            str_starts_with($path, 'users') ||
            str_starts_with($path, 'messages') ||
            str_starts_with($path, 'admin/conversations')
        ) {
            return 'auth';
        }

        // Catalog Service (Port 8002)
        if (
            str_starts_with($path, 'products') ||
            str_starts_with($path, 'categories') ||
            str_starts_with($path, 'brands') ||
            str_starts_with($path, 'banners')
        ) {
            return 'catalog';
        }

        // Order Service (Port 8003)
        if (
            str_starts_with($path, 'orders') ||
            str_starts_with($path, 'admin/orders') ||
            str_starts_with($path, 'cart') ||
            str_starts_with($path, 'coupons') ||
            str_starts_with($path, 'shipping') ||
            str_starts_with($path, 'reviews') ||
            str_starts_with($path, 'sales') ||
            str_starts_with($path, 'vouchers')
        ) {
            return 'order';
        }

        // Payment Service (Port 8004)
        if (
            str_starts_with($path, 'payment') ||
            str_starts_with($path, 'payments') ||
            str_starts_with($path, 'admin/finance')
        ) {
            return 'payment';
        }

        return null;
    }
}
