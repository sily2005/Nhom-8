<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpsertProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('image') && !$this->has('image_url')) {
            $merge['image_url'] = $this->input('image');
        }
        if ($this->has('oldPrice') && !$this->has('old_price')) {
            $merge['old_price'] = $this->input('oldPrice');
        }
        if ($this->has('categoryId') && !$this->has('category_id')) {
            $merge['category_id'] = $this->input('categoryId');
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $productId = $product?->id;
        $required = $product === null ? 'required' : 'sometimes';

        return [
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.$productId],
            'sku' => [$required, 'string', 'max:100', 'regex:/^[A-Z0-9_-]+$/i', 'unique:products,sku,'.$productId],
            'description' => ['nullable', 'string'],
            'price' => [$required, 'numeric', 'min:1000'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => [$required, 'integer', 'min:0', 'max:1000000'],
            'brand' => [$required, 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:50'],
            'image_url' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'colors' => ['nullable', 'array'],
            'sizes' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric'],
            'variants.*.sale_price' => ['nullable', 'numeric'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
