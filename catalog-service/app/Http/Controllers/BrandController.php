<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * List all brands with product counts.
     *
     * @group Brand Management
     */
    public function index(): JsonResponse
    {
        $brands = Brand::latest()->get()->map(function (Brand $brand) {
            $productCount = Product::where('brand', $brand->name)
                ->count();
            $brandArray = $brand->toArray();
            $brandArray['products_count'] = $productCount;
            return $brandArray;
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách thương hiệu thành công.',
            'data' => $brands,
            'errors' => null,
        ]);
    }

    /**
     * Create a brand.
     *
     * @group Brand Management
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:brands,slug'],
            'logo_path' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $brand = Brand::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo thương hiệu mới thành công.',
            'data' => $brand,
            'errors' => null,
        ], 201);
    }

    /**
     * Get a brand by ID.
     *
     * @group Brand Management
     */
    public function show(Brand $brand): JsonResponse
    {
        $products = Product::where('brand', $brand->name)
            ->get();

        $data = $brand->toArray();
        $data['products'] = $products;

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin thương hiệu thành công.',
            'data' => $data,
            'errors' => null,
        ]);
    }

    /**
     * Update a brand.
     *
     * @group Brand Management
     */
    public function update(Request $request, Brand $brand): JsonResponse
    {
        if ($brand->slug === 'khac' || $brand->slug === 'other' || mb_strtolower($brand->name) === 'khác') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chỉnh sửa thương hiệu mặc định của hệ thống.',
                'data' => null,
                'errors' => ['brand' => ['Thương hiệu mặc định của hệ thống không thể sửa.']],
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:brands,slug,'.$brand->id],
            'logo_path' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $oldName = $brand->name;

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $brand->update($validated);

        // If name changed, update products referencing this brand name
        if (isset($validated['name']) && $validated['name'] !== $oldName) {
            Product::where('brand', $oldName)->update(['brand' => $validated['name']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thương hiệu thành công.',
            'data' => $brand->fresh(),
            'errors' => null,
        ]);
    }

    /**
     * Delete a brand.
     *
     * @group Brand Management
     */
    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->slug === 'khac' || $brand->slug === 'other' || mb_strtolower($brand->name) === 'khác') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa thương hiệu mặc định của hệ thống.',
                'data' => null,
                'errors' => ['brand' => ['Thương hiệu mặc định của hệ thống không thể xóa.']],
            ], 422);
        }

        // Find or create default "Khác" brand
        $defaultBrand = Brand::firstOrCreate(
            ['slug' => 'khac'],
            [
                'name' => 'Khác',
                'logo_path' => null,
                'is_active' => true,
            ]
        );

        // Reassign all active products referencing this brand to default "Khác"
        Product::where('brand', $brand->name)->update(['brand' => $defaultBrand->name]);

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thương hiệu và chuyển toàn bộ sản phẩm về thương hiệu mặc định "Khác".',
            'data' => null,
            'errors' => null,
        ]);
    }
}
