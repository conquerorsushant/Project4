<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\ContactInquiryController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\CustomerDetailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Fortify handles: POST /register, POST /login, POST /forgot-password,
| POST /reset-password, POST /email/verification-notification,
| GET /email/verify/{id}/{hash}
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Categories
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);

// Listings (public browse)
Route::get('listings', [ListingController::class, 'index']);
Route::get('listings/{slug}', [ListingController::class, 'show']);

// Search
Route::get('search', SearchController::class);

// Reviews (public read)
Route::get('listings/{listing}/reviews', [ReviewController::class, 'index']);

// Reviews (public submit — goes to moderation)
Route::post('listings/{listing}/reviews', [ReviewController::class, 'store']);

// Contact inquiries (public submit)
Route::post('listings/{listing}/inquiries', [ContactInquiryController::class, 'store']);

// Email verification for reviews and inquiries (signed URLs)
Route::get('verify/review/{review}', [VerificationController::class, 'verifyReview'])
    ->name('review.verify');
Route::get('verify/inquiry/{inquiry}', [VerificationController::class, 'verifyInquiry'])
    ->name('inquiry.verify');

// User email verification via signed URL (no auth required — link from email)
Route::get('email/verify-custom/{id}/{hash}', [VerificationController::class, 'verifyUserEmail'])
    ->name('verification.verify.custom');

// Legacy: Waitlist (customer details from the old form)
Route::apiResource('customer-details', CustomerDetailController::class);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum API tokens)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth / Profile
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
    Route::post('user/profile-picture', [AuthController::class, 'updateProfilePicture']);

    // Subscription status (no verification needed — onboarding page checks this)
    Route::get('subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('subscriptions/sync', [SubscriptionController::class, 'sync']);

    // My listings — CRUD (no verification needed so users can create/edit after signup)
    Route::get('my/listings', [ListingController::class, 'myListings']);
    Route::post('listings', [ListingController::class, 'store']);
    Route::put('listings/{listing}', [ListingController::class, 'update']);
    Route::delete('listings/{listing}', [ListingController::class, 'destroy']);

    // Listing media (no verification needed — let them build their listing)
    Route::post('listings/{listing}/cover', [ListingController::class, 'uploadCover']);
    Route::post('listings/{listing}/gallery', [ListingController::class, 'uploadGallery']);
    Route::delete('listings/{listing}/gallery/{mediaId}', [ListingController::class, 'deleteGalleryImage']);

    // My inquiries (no verification needed)
    Route::get('my/inquiries', [ContactInquiryController::class, 'myInquiries']);
    Route::patch('inquiries/{inquiry}/read', [ContactInquiryController::class, 'markRead']);

    /*
    |----------------------------------------------------------------------
    | Verified email required for publishing & financial operations
    |----------------------------------------------------------------------
    */
    Route::middleware('verified')->group(function () {

        // Submit listing for review
        Route::post('listings/{listing}/submit', [ListingController::class, 'submitForReview']);

        // Featured listing checkout
        Route::post('listings/{listing}/feature', [ListingController::class, 'featureCheckout']);

        // Subscriptions (checkout/portal/cancel/resume require verification)
        Route::post('subscriptions/checkout', [SubscriptionController::class, 'checkout']);
        Route::post('subscriptions/portal', [SubscriptionController::class, 'portal']);
        Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscriptions/resume', [SubscriptionController::class, 'resume']);

        // Billing
        Route::get('billing/invoices', [BillingController::class, 'invoices']);
        Route::get('billing/invoices/{invoice}', [BillingController::class, 'downloadInvoice']);

        // Claim profile
        Route::post('listings/{listing}/claim', [ClaimController::class, 'store']);
    });
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no auth — verified by Cashier via webhook secret)
|--------------------------------------------------------------------------
*/
Route::post('stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook']);
