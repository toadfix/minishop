<?php

namespace Minishop\Actions;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Minishop\Models\Product;

/**
 * Builds a paginated product listing shared by the storefront controller, the
 * Livewire product list and the REST API. When a search term is present the
 * query runs through Laravel Scout (database engine by default, swappable to
 * Meilisearch/Algolia by the host); facet filters are applied either way.
 */
class SearchProducts
{
    /**
     * @param  array<string, mixed>  $filters  search, category, tag, price_min, price_max, stock
     * @param  Closure(Builder<Product>): void|null  $tap  add eager loads / aggregates to the underlying query
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, int $perPage = 24, ?Closure $tap = null): LengthAwarePaginator
    {
        $apply = function (Builder $query) use ($filters, $tap): void {
            $query->where('is_active', true);
            $this->applyFacets($query, $filters);

            if ($tap) {
                $tap($query);
            }
        };

        $term = trim((string) ($filters['search'] ?? ''));

        if ($term !== '') {
            return Product::search($term)
                ->query($apply)
                ->paginate($perPage)
                ->withQueryString();
        }

        // No search term: plain browse, newest first (search uses relevance order).
        $query = Product::query()->latest();
        $apply($query);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFacets(Builder $query, array $filters): void
    {
        $query
            ->when(! empty($filters['category']), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('slug', $filters['category'])
            ))
            ->when(! empty($filters['tag']), fn (Builder $q) => $q->whereHas(
                'tags', fn (Builder $t) => $t->where('slug', $filters['tag'])
            ))
            ->when(isset($filters['price_min']) && $filters['price_min'] !== '', fn (Builder $q) => $q->where(
                'price', '>=', (int) round((float) $filters['price_min'] * 100)
            ))
            ->when(isset($filters['price_max']) && $filters['price_max'] !== '', fn (Builder $q) => $q->where(
                'price', '<=', (int) round((float) $filters['price_max'] * 100)
            ))
            ->when(! empty($filters['stock']), function (Builder $q) use ($filters): void {
                // Bundled products have computed (not stored) stock — exclude them.
                $q->where('type', '!=', 'bundled');

                if ($filters['stock'] === 'in_stock') {
                    $q->where('stock_quantity', '>', 0);
                } elseif ($filters['stock'] === 'out_of_stock') {
                    $q->where('stock_quantity', 0);
                }
            });
    }
}
