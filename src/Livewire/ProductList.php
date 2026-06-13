<?php

namespace Minishop\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Minishop\Models\Category;
use Minishop\Models\Product;

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

    public function render()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['images'])
            ->when($this->category !== '', function ($query): void {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $this->category));
            })
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(24);

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
