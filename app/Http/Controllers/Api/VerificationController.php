<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewContactInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Verify a user's email via signed URL (no auth required).
     * The link is sent via VerifyEmailNotification and uses a signed URL.
     */
    public function verifyUserEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        // Validate the hash matches the user's email
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->verificationPage('Verification Failed', 'This verification link is invalid.', false);
        }

        // Validate the signed URL
        if (!$request->hasValidSignature()) {
            return $this->verificationPage('Link Expired', 'This verification link has expired. Please log in and request a new one.', false);
        }

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/onboarding?verified=1');
        }

        // Mark as verified
        $user->markEmailAsVerified();

        return redirect($frontendUrl . '/onboarding?verified=1');
    }

    /**
     * Verify a review via signed URL.
     */
    public function verifyReview(Request $request, Review $review)
    {
        if (!$request->hasValidSignature()) {
            return $this->verificationPage('Verification Failed', 'This verification link is invalid or has expired.', false);
        }

        if ($review->is_verified) {
            return $this->verificationPage('Already Verified', 'Your review has already been verified. It is now pending admin approval.', true);
        }

        $review->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return $this->verificationPage(
            'Review Verified!',
            'Thank you for verifying your email. Your review for "' . $review->listing->company_name . '" is now pending admin approval.',
            true
        );
    }

    /**
     * Verify a contact inquiry via signed URL.
     */
    public function verifyInquiry(Request $request, ContactInquiry $inquiry)
    {
        if (!$request->hasValidSignature()) {
            return $this->verificationPage('Verification Failed', 'This verification link is invalid or has expired.', false);
        }

        if ($inquiry->is_verified) {
            return $this->verificationPage('Already Verified', 'Your inquiry has already been verified and sent to the business.', true);
        }

        $inquiry->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        // Now notify the listing owner
        if ($inquiry->listing && $inquiry->listing->user) {
            $inquiry->listing->user->notify(new NewContactInquiry($inquiry, $inquiry->listing));
        }

        return $this->verificationPage(
            'Inquiry Verified!',
            'Thank you for verifying your email. Your estimate request has been sent to "' . $inquiry->listing->company_name . '". They will contact you soon.',
            true
        );
    }

    /**
     * Render a simple HTML verification result page that redirects to frontend.
     */
    private function verificationPage(string $title, string $message, bool $success)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $color = $success ? '#10b981' : '#ef4444';

        return response(<<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title} — NeedAnEstimate</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0a0a1a; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 48px 36px; max-width: 500px; text-align: center; }
                .icon { font-size: 48px; margin-bottom: 16px; }
                h1 { font-size: 24px; margin-bottom: 12px; color: {$color}; }
                p { color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 24px; }
                a { display: inline-block; padding: 12px 32px; background: #f7931a; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 600; }
                a:hover { background: #e67e22; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">{$this->statusIcon($success)}</div>
                <h1>{$title}</h1>
                <p>{$message}</p>
                <a href="{$frontendUrl}">Go to NeedAnEstimate</a>
            </div>
        </body>
        </html>
        HTML);
    }

    private function statusIcon(bool $success): string
    {
        return $success ? '✅' : '❌';
    }
}
