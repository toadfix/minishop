<?php

namespace Minishop\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Minishop\Actions\SearchProducts;
use Minishop\Models\Category;

class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'category'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category']);
        $this->resetPage();
    }

    public function render(SearchProducts $search)
    {
        $products = $search->paginate(
            ['search' => $this->search, 'category' => $this->category],
            perPage: 24,
            tap: fn ($query) => $query->with(['images']),
        );

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('minishop::livewire.storefront.product-list', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
