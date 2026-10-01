<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * List all addresses for a user.
     *
     * @group Address Management
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $addresses = Address::where('user_id', $validated['user_id'])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách địa chỉ thành công.',
            'data' => $addresses,
            'errors' => null,
        ]);
    }

    /**
     * Create a new address for a user.
     *
     * @group Address Management
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'        => ['required', 'integer', 'exists:users,id'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone'          => ['required', 'string', 'regex:/^0(3|5|7|8|9)\d{8}$/'],
            'province'       => ['required', 'string', 'max:100'],
            'district'       => ['required', 'string', 'max:100'],
            'ward'           => ['required', 'string', 'max:100'],
            'street_address' => ['required', 'string', 'max:255'],
            'is_default'     => ['sometimes', 'boolean'],
        ]);

        $address = DB::transaction(function () use ($validated): Address {
            $isDefault = $validated['is_default'] ?? false;

            // If this is the first address or explicitly set as default, unset others first
            $existingCount = Address::where('user_id', $validated['user_id'])->count();
            if ($existingCount === 0 || $isDefault) {
                Address::where('user_id', $validated['user_id'])
                    ->update(['is_default' => false]);
                $validated['is_default'] = true;
            }

            return Address::create($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm địa chỉ mới thành công.',
            'data' => $address,
            'errors' => null,
        ], 201);
    }

    /**
     * Update an existing address.
     *
     * @group Address Management
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['sometimes', 'string', 'max:100'],
            'phone'          => ['sometimes', 'string', 'regex:/^0(3|5|7|8|9)\d{8}$/'],
            'province'       => ['sometimes', 'string', 'max:100'],
            'district'       => ['sometimes', 'string', 'max:100'],
            'ward'           => ['sometimes', 'string', 'max:100'],
            'street_address' => ['sometimes', 'string', 'max:255'],
            'is_default'     => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($address, $validated): void {
            if (!empty($validated['is_default'])) {
                Address::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
            $address->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công.',
            'data' => $address->fresh(),
            'errors' => null,
        ]);
    }

    /**
     * Delete an address.
     *
     * @group Address Management
     */
    public function destroy(Address $address): JsonResponse
    {
        $userId = $address->user_id;
        $wasDefault = $address->is_default;

        $address->delete();

        // If deleted address was default, promote the next address
        if ($wasDefault) {
            $next = Address::where('user_id', $userId)->orderBy('id')->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa địa chỉ thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }

    /**
     * Set an address as the default for a user.
     *
     * @group Address Management
     */
    public function setDefault(Address $address): JsonResponse
    {
        DB::transaction(function () use ($address): void {
            Address::where('user_id', $address->user_id)
                ->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt làm địa chỉ mặc định.',
            'data' => $address->fresh(),
            'errors' => null,
        ]);
    }
}
