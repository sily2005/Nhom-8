<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Create an order from items or user's cart.
     *
     * @group Order Management
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->orderService->createOrder($validated);

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công.',
                'data' => $result['order']->load(['items', 'coupon']),
                'pay_url' => $result['pay_url'],
                'errors' => null,
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            $statusCode = is_numeric($code) && (int) $code >= 400 && (int) $code < 600 ? (int) $code : 422;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => ['order' => [$e->getMessage()]],
            ], $statusCode);
        }
    }

    /**
     * List orders (supports admin & user scoping).
     *
     * @group Order Management
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'nullable', 'string'],
            'search' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->orderService->listOrders($validated);
        $orders = $result['orders'];

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công.',
            'data' => $orders->items(),
            'stats' => $result['stats'],
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
            'errors' => null,
        ]);
    }

    /**
     * Get order statistics for Admin.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->orderService->getOrderStats(),
        ]);
    }

    /**
     * Get real-time product sales summary (sold count grouped by product_id).
     */
    public function salesSummary(): JsonResponse
    {
        $sales = \App\Models\OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotIn('orders.order_status', ['cancelled'])
            ->whereNotIn('orders.status', ['cancelled'])
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sold_count')
            ->groupBy('order_items.product_id')
            ->pluck('sold_count', 'product_id');

        return response()->json([
            'success' => true,
            'message' => 'Lấy tổng hợp lượt bán thành công.',
            'data' => $sales,
            'errors' => null,
        ]);
    }

    /**
     * Show an order by ID.
     *
     * @group Order Management
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin đơn hàng thành công.',
            'data' => $order->load(['items', 'coupon']),
            'errors' => null,
        ]);
    }

    /**
     * Update order status or payment status (Admin).
     *
     * @group Order Management
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'order_status' => ['sometimes', 'string', 'in:pending,processing,shipping,delivered,cancelled,refund_pending,refunded'],
            'payment_status' => ['sometimes', 'string', 'in:unpaid,pending,paid,failed,refunded,refund_pending'],
            'ghn_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'note' => ['sometimes', 'nullable', 'string'],
            'refund_reason' => ['sometimes', 'nullable', 'string'],
        ]);

        $updatedOrder = $this->orderService->updateOrderStatus($order, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái đơn hàng thành công.',
            'data' => $updatedOrder,
            'errors' => null,
        ]);
    }

    /**
     * Create GHN shipping order and persist ghn_code.
     *
     * @group Order Management
     */
    public function createGhnShipping(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'to_district_id' => ['sometimes', 'integer'],
            'to_ward_code' => ['sometimes', 'string'],
        ]);

        try {
            $result = $this->orderService->createGhnShipping($order, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo vận đơn GHN thành công: ' . $result['ghn_code'],
                'data' => [
                    'order' => $result['order'],
                    'ghn_code' => $result['ghn_code'],
                    'ghn_details' => $result['ghn_details'],
                ],
                'errors' => null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo đơn GHN thất bại: ' . $e->getMessage(),
                'data' => null,
                'errors' => ['ghn' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Mark an order as paid (called by payment-service upon successful payment).
     */
    public function markPaid(Request $request, $order): JsonResponse
    {
        $orderModel = $order instanceof Order
            ? $order
            : Order::where('id', $order)->orWhere('order_number', $order)->orWhere('order_code', $order)->first();

        if (!$orderModel) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        $orderModel->update([
            'payment_status' => 'paid',
            'order_status' => 'pending',
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Đơn hàng #{$orderModel->id} đã được cập nhật thanh toán thành công.",
            'data' => $orderModel->fresh(),
        ]);
    }
}