<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Models\Listing;
use App\Notifications\NewContactInquiry;
use App\Notifications\VerifyInquiryEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactInquiryController extends Controller
{
    /**
     * Submit a contact inquiry for a listing (public, requires email verification).
     */
    public function store(Request $request, string $listingSlug): JsonResponse
    {
        $listing = Listing::where('status', 'active')
            ->where(function ($q) use ($listingSlug) {
                $q->where('slug', $listingSlug)->orWhere('id', $listingSlug);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $validated['listing_id'] = $listing->id;
        $validated['is_verified'] = false;

        $inquiry = ContactInquiry::create($validated);

        // Send verification email to the inquirer
        Notification::route('mail', $inquiry->email)
            ->notify(new VerifyInquiryEmail($inquiry));

        return response()->json([
            'success' => true,
            'message' => 'Your estimate request has been received! Please check your email to verify your request.',
        ], 201);
    }

    /**
     * Auth: list inquiries for the user's listings.
     */
    public function myInquiries(Request $request): JsonResponse
    {
        $listingIds = $request->user()->listings()->pluck('id');

        $inquiries = ContactInquiry::whereIn('listing_id', $listingIds)
            ->with('listing:id,title,slug,company_name')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $inquiries,
        ]);
    }

    /**
     * Auth: mark an inquiry as read.
     */
    public function markRead(Request $request, ContactInquiry $inquiry): JsonResponse
    {
        // Verify ownership
        $listingIds = $request->user()->listings()->pluck('id');
        if (!$listingIds->contains($inquiry->listing_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $inquiry->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry marked as read.',
        ]);
    }
}
