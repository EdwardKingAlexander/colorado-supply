<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'parent_id'])
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $childrenWithProducts = $categories
            ->filter(fn (Category $category) => $category->parent_id !== null && $category->products_count > 0)
            ->groupBy('parent_id')
            ->map(fn ($group) => $group->isNotEmpty());

        $categories = $categories
            ->filter(function (Category $category) use ($childrenWithProducts) {
                if ($category->products_count > 0) {
                    return true;
                }

                if ($category->parent_id === null) {
                    return (bool) ($childrenWithProducts->get($category->id) ?? false);
                }

                return false;
            })
            ->values();

        return CategoryResource::collection($categories);
    }
}
