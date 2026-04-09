<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Notifications\ListingSubmittedForReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListingController extends Controller
{
    /**
     * Public browse: list active listings with filters + pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Listing::active()
            ->with(['category', 'user:id,name', 'media'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating');

        // Featured first
        $query->orderByDesc('is_featured');

        // Filters
        if ($request->filled('category')) {
            $query->inCategory($request->category);
        }

        if ($request->filled('city') || $request->filled('state') || $request->filled('zip')) {
            $query->searchByLocation(
                $request->city,
                $request->state,
                $request->zip
            );
        }

        if ($request->filled('q')) {
            $query->searchByKeyword($request->q);
        }

        $query->orderByDesc('published_at');

        $listings = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Public: get a single listing by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $listing = Listing::where('slug', $slug)
            ->active()
            ->with([
                'category',
                'user:id,name',
                'faqs',
                'approvedReviews',
                'media',
            ])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    /**
     * Auth: list the current user's own listings.
     */
    public function myListings(Request $request): JsonResponse
    {
        $listings = $request->user()
            ->listings()
            ->with(['category', 'media'])
            ->withCount('approvedReviews')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Auth: create a new draft listing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'operating_hours' => ['nullable', 'array'],
            'keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'yelp_url' => ['nullable', 'url', 'max:255'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'draft';

        $listing = Listing::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Listing created as draft.',
            'data' => $listing,
        ], 201);
    }

    /**
     * Auth: update own listing.
     */
    public function update(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'company_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'operating_hours' => ['nullable', 'array'],
            'keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'yelp_url' => ['nullable', 'url', 'max:255'],
        ]);

        $listing->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Listing updated.',
            'data' => $listing->fresh(),
        ]);
    }

    /**
     * Auth: soft-delete own listing.
     */
    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $listing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Listing deleted.',
        ]);
    }

    /**
     * Auth: submit a draft listing for review.
     * Both paid and unpaid users can submit. Paid users get priority review.
     */
    public function submitForReview(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($listing->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft listings can be submitted for review.',
            ], 422);
        }

        $isPaid = $request->user()->subscribed('default');

        $listing->update([
            'status' => 'pending_review',
            'is_priority' => $isPaid,
        ]);

        $message = $isPaid
            ? 'Listing submitted for priority review. You will be notified when it is approved.'
            : 'Listing submitted for review. Subscribe to get priority reviews! You will be notified when it is approved.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $listing->fresh(),
        ]);
    }

    /**
     * Auth: upload cover image for a listing.
     */
    public function uploadCover(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'cover' => ['required', 'image', 'max:10240'], // 10MB
        ]);

        $listing->addMediaFromRequest('cover')
            ->toMediaCollection('cover');

        return response()->json([
            'success' => true,
            'message' => 'Cover image uploaded.',
            'data' => [
                'url' => $listing->getFirstMediaUrl('cover'),
                'thumb' => $listing->getFirstMediaUrl('cover', 'thumb'),
            ],
        ]);
    }

    /**
     * Auth: upload gallery images for a listing.
     */
    public function uploadGallery(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'max:10240'],
        ]);

        foreach ($request->file('images') as $image) {
            $listing->addMedia($image)
                ->toMediaCollection('gallery');
        }

        $gallery = $listing->getMedia('gallery')->map(fn($m) => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'thumb' => $m->getUrl('thumb'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery images uploaded.',
            'data' => $gallery,
        ]);
    }

    /**
     * Auth: delete a gallery image.
     */
    public function deleteGalleryImage(Request $request, Listing $listing, int $mediaId): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $media = $listing->media()->findOrFail($mediaId);
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image removed.',
        ]);
    }

    /**
     * Auth: request featured status (one-time Stripe Checkout).
     */
    public function featureCheckout(Request $request, Listing $listing): JsonResponse
    {
        if ((int) $listing->user_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$listing->isLive()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing must be active to be featured.',
            ], 422);
        }

        $user = $request->user();

        $checkout = $user->checkout([
            config('services.stripe.price_featured', 'price_featured') => 1,
        ], [
            'success_url' => config('app.frontend_url') . '/dashboard/listings?featured=success&listing=' . $listing->id,
            'cancel_url' => config('app.frontend_url') . '/dashboard/listings?featured=cancelled',
            'metadata' => [
                'listing_id' => $listing->id,
                'type' => 'featured',
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'checkout_url' => $checkout->url,
            ],
        ]);
    }
}
