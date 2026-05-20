<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishop\Database\Factories\ProductFactory;
use Minishop\Enums\ProductType;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'price',
        'compare_price',
        'sku',
        'stock_quantity',
        'weight_grams',
        'is_active',
        'on_sale',
        'low_stock_notified',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'integer',
            'compare_price' => 'integer',
            'stock_quantity' => 'integer',
            'weight_grams' => 'integer',
            'is_active' => 'boolean',
            'on_sale' => 'boolean',
            'low_stock_notified' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('type') && $product->getOriginal('type') !== null) {
                $product->type = $product->getOriginal('type');
                Log::warning("Attempted type change on product {$product->id}");
            }
        });

        static::deleting(function (Product $product): void {
            if ($product->asComponentIn()->exists()) {
                abort(409, 'This product is a component of one or more bundles. Remove it from those bundles first.');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_related', 'product_id', 'related_product_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->whereNull('variant_id')->orderBy('sort_order');
    }

    public function allImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function asComponentIn(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'component_product_id');
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }

    public function isBundled(): bool
    {
        return $this->type === ProductType::Bundled;
    }

    public function getEffectiveStock(): int
    {
        if (! $this->isBundled()) {
            return $this->stock_quantity;
        }

        $this->loadMissing('bundleItems.componentProduct', 'bundleItems.componentVariant');

        if ($this->bundleItems->isEmpty()) {
            return 0;
        }

        return $this->bundleItems->min(function (BundleItem $item) {
            $available = $item->component_variant_id
                ? ($item->componentVariant?->stock_quantity ?? 0)
                : $item->componentProduct->stock_quantity;

            return intdiv($available, $item->quantity);
        });
    }

    public function getEffectiveWeight(): ?int
    {
        if (! $this->isBundled()) {
            return $this->weight_grams;
        }

        $this->loadMissing('bundleItems.componentProduct', 'bundleItems.componentVariant');

        if ($this->bundleItems->isEmpty()) {
            return null;
        }

        return $this->bundleItems->sum(function (BundleItem $item) {
            $weight = $item->component_variant_id
                ? ($item->componentVariant?->weight_grams ?? $item->componentProduct->weight_grams)
                : $item->componentProduct->weight_grams;

            return ($weight ?? 0) * $item->quantity;
        });
    }
}
