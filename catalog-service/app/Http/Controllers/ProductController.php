<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Danh sách sản phẩm kèm bộ lọc tìm kiếm
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $categoryId = $request->query('category_id');
        $brand = $request->query('brand');
        $tag = $request->query('tag');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'newest');
        $perPage = (int) $request->query('per_page', 24);

        $query = Product::with(['category', 'variants'])
            ->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        } elseif ($category && $category !== 'all') {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category)->orWhere('name', $category);
            });
        }

        if ($brand && $brand !== 'all') {
            $query->where('brand', 'like', "%{$brand}%");
        }

        if ($tag && $tag !== 'all') {
            $query->where('tag', $tag);
        }

        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('stock', 'desc'),
            default => $query->latest(),
        };

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    /**
     * Chi tiết sản phẩm theo ID hoặc Slug
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $product = Product::with(['category', 'variants', 'brandRef'])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Thêm sản phẩm mới (Admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer'],
            'brand' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'colors' => ['nullable', 'array'],
            'sizes' => ['nullable', 'array'],
            'tag' => ['nullable', 'string'],
            'sku' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['sku'] = $validated['sku'] ?? ('SKU-' . strtoupper(Str::random(8)));
        $validated['is_active'] = true;

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm mới thành công.',
            'data' => $product->load('category'),
        ], 201);
    }

    /**
     * Cập nhật sản phẩm (Admin)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer'],
            'brand' => ['sometimes', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'colors' => ['nullable', 'array'],
            'sizes' => ['nullable', 'array'],
            'tag' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => $product->fresh()->load('category'),
        ]);
    }

    /**
     * Xóa sản phẩm
     */
    public function destroy($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm thành công.',
        ]);
    }

    /**
     * Trừ tồn kho khi có đơn hàng (Gọi nội bộ từ Order Service)
     */
    public function deductStock(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $qty = (int) ($item['quantity'] ?? 1);

            if ($productId && $qty > 0) {
                $product = Product::find($productId);
                if ($product && $product->stock >= $qty) {
                    $product->decrement('stock', $qty);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Đã trừ tồn kho sản phẩm.']);
    }
}
