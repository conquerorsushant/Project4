<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use App\Notifications\VerifyReviewEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReviewController extends Controller
{
    /**
     * List approved reviews for a listing (public).
     */
    public function index(string $listingSlug): JsonResponse
    {
        $listing = Listing::where('status', 'active')
            ->where(function ($q) use ($listingSlug) {
                $q->where('slug', $listingSlug)->orWhere('id', $listingSlug);
            })
            ->firstOrFail();

        $reviews = $listing->approvedReviews()
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Submit a review for a listing (public, requires email verification).
     */
    public function store(Request $request, string $listingSlug): JsonResponse
    {
        $listing = Listing::where('status', 'active')
            ->where(function ($q) use ($listingSlug) {
                $q->where('slug', $listingSlug)->orWhere('id', $listingSlug);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:255'],
            'reviewer_email' => ['required', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['listing_id'] = $listing->id;
        $validated['user_id'] = $request->user()?->id;
        $validated['is_approved'] = false;
        $validated['is_verified'] = false;

        $review = Review::create($validated);

        // Send verification email
        Notification::route('mail', $review->reviewer_email)
            ->notify(new VerifyReviewEmail($review));

        return response()->json([
            'success' => true,
            'message' => 'Review submitted! Please check your email to verify your review.',
            'data' => $review,
        ], 201);
    }
}
