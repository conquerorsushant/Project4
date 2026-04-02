<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all categories with subcategories and active listing counts.
     */
    public function index(): JsonResponse
    {
        $categories = Category::topLevel()
            ->active()
            ->ordered()
            ->with(['children' => fn($q) => $q->active()->ordered()])
            ->withCount(['listings' => fn($q) => $q->active()])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get a single category with its subcategories.
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['children' => fn($q) => $q->active()->ordered()]);
        $category->loadCount(['listings' => fn($q) => $q->active()]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }
}
