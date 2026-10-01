<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'brand_id', 'name', 'slug', 'sku', 'description', 'price', 'old_price', 'stock', 'brand', 'tag', 'image_url', 'images', 'colors', 'sizes', 'is_active'])]
class Product extends Model
{
    use SoftDeletes;

    protected $appends = ['is_deleted'];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'stock' => 'integer',
            'images' => 'array',
            'colors' => 'array',
            'sizes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getIsDeletedAttribute(): bool
    {
        return $this->trashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productImages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}