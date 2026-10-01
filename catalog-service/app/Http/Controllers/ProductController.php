<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertProductRequest;
use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * List products with pagination, category filtering, text search, and soft-delete exclusion.
     *
     * @group Product Management
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::with(['category', 'variants'])
            ->when($validated['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($validated['brand'] ?? null, fn ($query, $brand) => $query->where('brand', $brand))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm thành công.',
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
            'errors' => null,
        ]);
    }

    /**
     * Create a new product.
     *
     * @group Product Management
     */
    public function store(UpsertProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['name'] = trim($validated['name']);
        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['sku'] = Str::upper(trim($validated['sku']));

        $variantsData = $request->input('variants');
        $product = Product::create($validated);

        if (is_array($variantsData)) {
            foreach ($variantsData as $v) {
                $product->variants()->create([
                    'sku' => !empty($v['sku']) ? $v['sku'] : ($product->sku . '-' . Str::random(5)),
                    'price' => $v['price'] ?? $product->price,
                    'sale_price' => $v['sale_price'] ?? null,
                    'stock' => isset($v['stock']) ? (int) $v['stock'] : 0,
                    'attributes' => $v['attributes'] ?? [],
                    'is_active' => $v['is_active'] ?? true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm mới thành công.',
            'data' => $product->load(['category', 'variants']),
            'errors' => null,
        ], 201);
    }

    /**
     * Get a single product by ID (excluding soft-deleted).
     *
     * @group Product Management
     */
    public function show(Product $product): JsonResponse
    {
        if ($product->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc đã bị xóa.',
                'data' => null,
                'errors' => ['product' => ['Sản phẩm đã bị xóa.']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin sản phẩm thành công.',
            'data' => $product->load(['category', 'variants']),
            'errors' => null,
        ]);
    }

    /**
     * Update a product.
     *
     * @group Product Management
     */
    public function update(UpsertProductRequest $request, Product $product): JsonResponse
    {
        if ($product->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật sản phẩm đã bị xóa.',
                'data' => null,
                'errors' => ['product' => ['Sản phẩm đã bị xóa.']],
            ], 404);
        }

        $validated = $request->validated();
        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }
        if (isset($validated['sku'])) {
            $validated['sku'] = Str::upper(trim($validated['sku']));
        }

        $product->update($validated);

        if ($request->has('variants') && is_array($request->input('variants'))) {
            $product->variants()->delete();
            foreach ($request->input('variants') as $v) {
                $product->variants()->create([
                    'sku' => !empty($v['sku']) ? $v['sku'] : ($product->sku . '-' . Str::random(5)),
                    'price' => $v['price'] ?? $product->price,
                    'sale_price' => $v['sale_price'] ?? null,
                    'stock' => isset($v['stock']) ? (int) $v['stock'] : 0,
                    'attributes' => $v['attributes'] ?? [],
                    'is_active' => $v['is_active'] ?? true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => $product->fresh()->load(['category', 'variants']),
            'errors' => null,
        ]);
    }

    /**
     * Soft delete a product.
     *
     * @group Product Management
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển sản phẩm vào thùng rác thành công (xóa mềm).',
            'data' => null,
            'errors' => null,
        ]);
    }

    /**
     * Check real-time stock availability for items.
     *
     * @group Stock Management
     */
    public function checkStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $productIds = collect($validated['items'])->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $outOfStock = [];
        $itemsStatus = [];
        $isAllAvailable = true;

        foreach ($validated['items'] as $item) {
            $pid = $item['product_id'];
            $reqQty = $item['quantity'];

            /** @var Product|null $p */
            $p = $products->get($pid);

            if (!$p) {
                $isAllAvailable = false;
                $outOfStock[] = [
                    'product_id' => $pid,
                    'name' => 'Sản phẩm #' . $pid,
                    'requested' => $reqQty,
                    'available_stock' => 0,
                    'reason' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.',
                ];
                $itemsStatus[] = [
                    'product_id' => $pid,
                    'available' => false,
                    'stock' => 0,
                ];
                continue;
            }

            $currentStock = (int) $p->stock;
            if ($currentStock < $reqQty) {
                $isAllAvailable = false;
                $outOfStock[] = [
                    'product_id' => $pid,
                    'name' => $p->name,
                    'requested' => $reqQty,
                    'available_stock' => $currentStock,
                    'reason' => 'Kho chỉ còn ' . $currentStock . ' sản phẩm.',
                ];
                $itemsStatus[] = [
                    'product_id' => $pid,
                    'name' => $p->name,
                    'available' => false,
                    'stock' => $currentStock,
                ];
            } else {
                $itemsStatus[] = [
                    'product_id' => $pid,
                    'name' => $p->name,
                    'available' => true,
                    'stock' => $currentStock,
                ];
            }
        }

        if (!$isAllAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'Một số sản phẩm không đủ số lượng tồn kho.',
                'data' => [
                    'is_available' => false,
                    'out_of_stock' => $outOfStock,
                    'items' => $itemsStatus,
                ],
                'errors' => ['stock' => $outOfStock],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Toàn bộ sản phẩm đều sẵn sàng trong kho.',
            'data' => [
                'is_available' => true,
                'items' => $itemsStatus,
            ],
            'errors' => null,
        ]);
    }

    /**
     * Deduct stock atomically within DB transaction.
     *
     * @group Stock Management
     */
    public function deductStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $deducted = DB::transaction(function () use ($validated) {
                $results = [];

                foreach ($validated['items'] as $item) {
                    $pid = (int) $item['product_id'];
                    $qty = (int) $item['quantity'];

                    $product = Product::where('id', $pid)
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        throw new Exception("Sản phẩm #{$pid} không tồn tại hoặc đã bị xóa.");
                    }

                    if ((int) $product->stock < $qty) {
                        throw new Exception("Sản phẩm \"{$product->name}\" không đủ tồn kho (yêu cầu {$qty}, trong kho còn {$product->stock}).");
                    }

                    $product->decrement('stock', $qty);

                    $results[] = [
                        'product_id' => $pid,
                        'name' => $product->name,
                        'deducted_quantity' => $qty,
                        'remaining_stock' => (int) $product->fresh()->stock,
                    ];
                }

                return $results;
            });

            return response()->json([
                'success' => true,
                'message' => 'Trừ tồn kho sản phẩm thành công.',
                'data' => $deducted,
                'errors' => null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => ['stock' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Restore stock when order is cancelled or rolled back.
     *
     * @group Stock Management
     */
    public function restoreStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                $pid = (int) $item['product_id'];
                $qty = (int) $item['quantity'];

                $product = Product::where('id', $pid)->lockForUpdate()->first();
                if ($product) {
                    $product->increment('stock', $qty);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Hoàn trả tồn kho thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}