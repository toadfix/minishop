<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Minishop\Database\Factories\ProductImageFactory;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'path',
        'alt_text',
        'sort_order',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk(config('minishop.image_disk'))->url($this->attributes['path']),
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
