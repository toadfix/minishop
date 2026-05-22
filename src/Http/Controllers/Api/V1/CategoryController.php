<?php

namespace Minishop\Http\Controllers\Api\V1;

use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\CategoryCollection;
use Minishop\Http\Resources\CategoryResource;
use Minishop\Models\Category;

class CategoryController extends Controller
{
    public function index(): CategoryCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return new CategoryCollection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        abort_unless($category->is_active, 404);

        $category->load(['products' => fn ($query) => $query->where('is_active', true)->with('images')]);

        return new CategoryResource($category);
    }
}
