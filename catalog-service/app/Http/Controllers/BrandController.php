<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }

    public function show($id): JsonResponse
    {
        $brand = Brand::with('products')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $brand]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'logo' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = true;

        $brand = Brand::create($validated);
        return response()->json(['success' => true, 'data' => $brand], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (!empty($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $brand->update($validated);
        return response()->json(['success' => true, 'data' => $brand]);
    }

    public function destroy($id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa thương hiệu thành công.']);
    }
}
