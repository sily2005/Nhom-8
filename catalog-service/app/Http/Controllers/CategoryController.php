<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List active and inactive categories.
     *
     * @group Category Management
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách danh mục thành công.',
            'data' => $categories,
            'errors' => null,
        ]);
    }

    /**
     * Create a category.
     *
     * @group Category Management
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $validated['name'] = trim($validated['name']);
        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục mới thành công.',
            'data' => $category,
            'errors' => null,
        ], 201);
    }

    /**
     * Get a category by ID.
     *
     * @group Category Management
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin danh mục thành công.',
            'data' => $category->load('products'),
            'errors' => null,
        ]);
    }

    /**
     * Update a category.
     *
     * @group Category Management
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        if ($category->slug === 'khac' || $category->slug === 'other' || mb_strtolower($category->name) === 'khác') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chỉnh sửa danh mục mặc định của hệ thống.',
                'data' => null,
                'errors' => ['category' => ['Danh mục mặc định của hệ thống không thể sửa.']],
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công.',
            'data' => $category->fresh(),
            'errors' => null,
        ]);
    }

    /**
     * Delete a category.
     *
     * @group Category Management
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->slug === 'khac' || $category->slug === 'other' || mb_strtolower($category->name) === 'khác') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục mặc định của hệ thống.',
                'data' => null,
                'errors' => ['category' => ['Danh mục mặc định của hệ thống không thể xóa.']],
            ], 422);
        }

        // Find or create default "Khác" category
        $defaultCat = Category::firstOrCreate(
            ['slug' => 'khac'],
            [
                'name' => 'Khác',
                'description' => 'Danh mục mặc định của hệ thống',
                'is_active' => true,
            ]
        );

        // Reassign all active products to default category
        $category->products()->update(['category_id' => $defaultCat->id]);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa danh mục và chuyển toàn bộ sản phẩm về danh mục mặc định "Khác".',
            'data' => null,
            'errors' => null,
        ]);
    }
}