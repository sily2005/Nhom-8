<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * List all banners.
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = filter_var($request->input('active_only', false), FILTER_VALIDATE_BOOLEAN);

        $query = Banner::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc');

        $banners = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách banner thành công.',
            'data' => $banners,
            'errors' => null,
        ]);
    }

    /**
     * Create a new banner.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tag' => ['sometimes', 'nullable', 'string', 'max:50'],
            'image' => ['required', 'string'],
            'link' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $banner = Banner::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công.',
            'data' => $banner,
            'errors' => null,
        ], 201);
    }

    /**
     * Get a single banner.
     */
    public function show(Banner $banner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết banner thành công.',
            'data' => $banner,
            'errors' => null,
        ]);
    }

    /**
     * Update a banner.
     */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tag' => ['sometimes', 'nullable', 'string', 'max:50'],
            'image' => ['sometimes', 'string'],
            'link' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $banner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công.',
            'data' => $banner->fresh(),
            'errors' => null,
        ]);
    }

    /**
     * Delete a banner.
     */
    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
