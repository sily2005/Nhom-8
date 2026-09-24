<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class GatewayController extends Controller
{
    public function auth(Request $request, ?string $any = null): Response|JsonResponse
    {
        return $this->forward($request, 'auth');
    }

    public function catalog(Request $request, ?string $any = null): Response|JsonResponse
    {
        return $this->forward($request, 'catalog');
    }

    public function order(Request $request, ?string $any = null): Response|JsonResponse
    {
        return $this->forward($request, 'order');
    }

    public function payment(Request $request, ?string $any = null): Response|JsonResponse
    {
        return $this->forward($request, 'payment');
    }

    private function forward(Request $request, string $service): JsonResponse|Response
    {
        $baseUrl = rtrim((string) config("services.microservices.{$service}"), '/');
        $targetUrl = $baseUrl.'/'.ltrim($request->path(), '/');

        try {
            $contentType = $request->header('Content-Type', 'application/json');
            $response = Http::withHeaders($this->forwardedHeaders($request))
                ->withOptions(['http_errors' => false])
                ->withBody($request->getContent(), $contentType)
                ->send($request->method(), $targetUrl, [
                    'query' => $request->query(),
                ]);

            $status = $response->status();
            $decoded = $response->json();

            // If downstream returned JSON or standard response
            if ($status >= 200 && $status < 300) {
                if (is_array($decoded)) {
                    $payload = [
                        'success' => true,
                        'data' => array_key_exists('data', $decoded) ? $decoded['data'] : $decoded,
                        'message' => $decoded['message'] ?? 'Thao tác thành công.',
                    ];
                    if (isset($decoded['pagination'])) {
                        $payload['pagination'] = $decoded['pagination'];
                    }
                    if (isset($decoded['stats'])) {
                        $payload['stats'] = $decoded['stats'];
                    }
                    if (isset($decoded['token'])) {
                        $payload['token'] = $decoded['token'];
                    }
                    if (isset($decoded['user'])) {
                        $payload['user'] = $decoded['user'];
                    }
                    return response()->json($payload, $status);
                }

                return response()->json([
                    'success' => true,
                    'data' => $response->body() ?: null,
                    'message' => 'Thao tác thành công.',
                ], $status);
            }

            // Error response envelope (4xx / 5xx)
            $code = 'ERROR_'.$status;
            $message = 'Đã có lỗi xảy ra.';

            if (is_array($decoded)) {
                if (!empty($decoded['message'])) {
                    $message = $decoded['message'];
                } elseif (!empty($decoded['error']['message'])) {
                    $message = $decoded['error']['message'];
                } elseif (!empty($decoded['error']) && is_string($decoded['error'])) {
                    $message = $decoded['error'];
                } elseif (!empty($decoded['errors']) && is_array($decoded['errors'])) {
                    $first = reset($decoded['errors']);
                    $message = is_array($first) ? ($first[0] ?? 'Dữ liệu không hợp lệ.') : (string) $first;
                }

                if (!empty($decoded['error']['code'])) {
                    $code = $decoded['error']['code'];
                } elseif ($status === 401) {
                    $code = 'UNAUTHORIZED';
                } elseif ($status === 403) {
                    $code = 'FORBIDDEN';
                } elseif ($status === 404) {
                    $code = 'NOT_FOUND';
                } elseif ($status === 422) {
                    $code = 'VALIDATION_ERROR';
                } elseif ($status >= 500) {
                    $code = 'INTERNAL_ERROR';
                    $message = 'Lỗi hệ thống máy chủ, vui lòng thử lại sau.';
                }
            } else {
                if ($status === 404) {
                    $code = 'NOT_FOUND';
                    $message = 'Tài nguyên không tìm thấy.';
                } elseif ($status >= 500) {
                    $code = 'INTERNAL_ERROR';
                    $message = 'Lỗi hệ thống máy chủ, vui lòng thử lại sau.';
                }
            }

            $errorPayload = [
                'success' => false,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ];

            if (is_array($decoded) && !empty($decoded['errors'])) {
                $errorPayload['error']['details'] = $decoded['errors'];
            }

            return response()->json($errorPayload, $status);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Dịch vụ '.$service.' tạm thời không khả dụng, vui lòng thử lại sau.',
                ],
            ], 503);
        }
    }

    private function forwardedHeaders(Request $request): array
    {
        $excluded = [
            'host',
            'content-length',
            'connection',
            'transfer-encoding',
            'content-encoding',
        ];

        return collect($request->headers->all())
            ->reject(fn (array $values, string $name): bool => in_array(strtolower($name), $excluded, true))
            ->map(fn (array $values): string => implode(', ', $values))
            ->all();
    }
}
