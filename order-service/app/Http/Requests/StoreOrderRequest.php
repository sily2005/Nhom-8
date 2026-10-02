<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation (Normalization).
     */
    protected function prepareForValidation(): void
    {
        $phoneInput = $this->input('phone') ?? $this->input('shipping_phone');
        $nameInput = $this->input('name') ?? $this->input('shipping_name') ?? $this->input('full_name');
        $addressInput = $this->input('address') ?? $this->input('shipping_address');

        $this->merge([
            'phone' => $phoneInput,
            'shipping_phone' => $phoneInput,
            'name' => $nameInput,
            'shipping_name' => $nameInput,
            'address' => $addressInput,
            'shipping_address' => $addressInput,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'shipping_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{8,11}$/'],
            'shipping_phone' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
            'shipping_address' => ['sometimes', 'nullable', 'string'],
            'to_district_id' => ['sometimes', 'nullable'],
            'to_ward_code' => ['sometimes', 'nullable'],
            'shipping_fee' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_method' => ['sometimes', 'string', 'in:cod,momo'],
            'coupon_id' => ['sometimes', 'nullable', 'integer', 'exists:coupons,id'],
            'coupon_code' => ['sometimes', 'nullable', 'string'],
            'note' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'min:1'],
            'items.*.product_name' => ['sometimes', 'nullable', 'string'],
            'items.*.name' => ['sometimes', 'nullable', 'string'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.size' => ['sometimes', 'nullable', 'string'],
            'items.*.selectedSize' => ['sometimes', 'nullable', 'string'],
            'items.*.color' => ['sometimes', 'nullable', 'string'],
            'items.*.selectedColor' => ['sometimes', 'nullable', 'string'],
            'items.*.sku' => ['sometimes', 'nullable', 'string'],
            'items.*.image' => ['sometimes', 'nullable', 'string'],
            'items.*.product_image' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
