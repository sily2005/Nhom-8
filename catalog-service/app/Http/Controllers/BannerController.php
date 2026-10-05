<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    public function show($id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'image' => ['required', 'string'],
            'link' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
        ]);

        $validated['is_active'] = true;
        $banner = Banner::create($validated);

        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'image' => ['sometimes', 'string'],
            'link' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $banner->update($validated);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function destroy($id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa banner thành công.']);
    }

    public function toggle($id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json(['success' => true, 'data' => $banner]);
    }
}
