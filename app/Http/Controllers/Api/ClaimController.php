<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaimRequest;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    /**
     * Auth: submit a claim request for an unclaimed listing.
     */
    public function store(Request $request, Listing $listing): JsonResponse
    {
        if ($listing->is_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'This listing has already been claimed.',
            ], 422);
        }

        // Check if user already has a pending claim
        $existing = ClaimRequest::where('listing_id', $listing->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending claim for this listing.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'verification_document' => ['nullable', 'file', 'max:10240'],
        ]);

        $claim = ClaimRequest::create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->hasFile('verification_document')) {
            $path = $request->file('verification_document')
                ->store('claim-documents', 'local');
            $claim->update(['verification_document' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Claim request submitted. An admin will review it.',
            'data' => $claim,
        ], 201);
    }
}
