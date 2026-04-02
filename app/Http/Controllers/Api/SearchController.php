<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search active listings.
     *
     * Query params:
     *  - q         : keyword (fulltext search on title, company, description, keywords)
     *  - category  : category slug
     *  - subcategory : subcategory slug
     *  - city      : city name (partial match)
     *  - state     : state (partial match)
     *  - zip       : zip code (exact match)
     *  - per_page  : results per page (default 12)
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = Listing::active()
            ->with(['category:id,name,slug', 'user:id,name', 'media']);

        // Keyword search
        if ($request->filled('q')) {
            $query->searchByKeyword($request->q);
        }

        // Category / subcategory
        if ($request->filled('subcategory')) {
            $query->inCategory($request->subcategory);
        } elseif ($request->filled('category')) {
            $query->inCategory($request->category);
        }

        // Location
        $query->searchByLocation(
            $request->city,
            $request->state,
            $request->zip
        );

        // Featured first, then by relevance / date
        $query->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        $results = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
