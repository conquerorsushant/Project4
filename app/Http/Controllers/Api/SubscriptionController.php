<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class SubscriptionController extends Controller
{
    /**
     * Get current subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'subscribed' => $user->subscribed('default'),
                'on_trial' => $user->onTrial('default'),
                'cancelled' => $user->subscription('default')?->canceled() ?? false,
                'on_grace_period' => $user->subscription('default')?->onGracePeriod() ?? false,
                'ends_at' => $user->subscription('default')?->ends_at,
                'subscription' => $user->subscription('default'),
            ],
        ]);
    }

    /**
     * Create a Stripe Checkout session for the basic plan subscription.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->subscribed('default')) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription.',
            ], 422);
        }

        $priceId = config('services.stripe.price_basic');

        if (!$priceId) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan is not configured.',
            ], 500);
        }

        $checkout = $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => config('app.frontend_url') . '/dashboard?subscription=success',
                'cancel_url' => config('app.frontend_url') . '/dashboard?subscription=cancelled',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'checkout_url' => $checkout->url,
            ],
        ]);
    }

    /**
     * Get Stripe Customer Portal URL for managing billing.
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json([
                'success' => false,
                'message' => 'No billing account found.',
            ], 404);
        }

        $portalUrl = $user->billingPortalUrl(
            config('app.frontend_url') . '/dashboard/billing'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'portal_url' => $portalUrl,
            ],
        ]);
    }

    /**
     * Cancel the subscription at the end of the current billing period.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || $subscription->canceled()) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription to cancel.'
            ], 422);
        }

        $subscription->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled. It will remain active until ' . $subscription->ends_at->toFormattedDateString() . '.',
            'data' => [
                'ends_at' => $subscription->ends_at,
            ],
        ]);
    }

    /**
     * Resume a cancelled subscription (while on grace period).
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->onGracePeriod()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription cannot be resumed.',
            ], 422);
        }

        $subscription->resume();

        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully.',
        ]);
    }

    /**
     * Sync subscription from Stripe to local DB.
     * Used when webhooks can't reach local dev, or as a fallback after checkout.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe customer found.',
            ], 404);
        }

        try {
            $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));

            // Get active subscriptions from Stripe for this customer
            $stripeSubscriptions = $stripeClient->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all',
                'limit' => 10,
            ]);

            $synced = 0;

            foreach ($stripeSubscriptions->data as $stripeSub) {
                // Skip subscriptions that are incomplete_expired
                if ($stripeSub->status === 'incomplete_expired') {
                    continue;
                }

                // Check if we already have this subscription locally
                $localSub = Subscription::where('stripe_id', $stripeSub->id)->first();

                if (!$localSub) {
                    // Create local subscription record
                    $localSub = $user->subscriptions()->create([
                        'type' => 'default',
                        'stripe_id' => $stripeSub->id,
                        'stripe_status' => $stripeSub->status,
                        'stripe_price' => $stripeSub->items->data[0]->price->id ?? null,
                        'quantity' => $stripeSub->items->data[0]->quantity ?? 1,
                        'trial_ends_at' => $stripeSub->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSub->trial_end) : null,
                        'ends_at' => $stripeSub->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at) : null,
                    ]);

                    // Also create subscription_items
                    foreach ($stripeSub->items->data as $item) {
                        $localSub->items()->create([
                            'stripe_id' => $item->id,
                            'stripe_product' => $item->price->product,
                            'stripe_price' => $item->price->id,
                            'quantity' => $item->quantity ?? 1,
                        ]);
                    }

                    $synced++;
                } else {
                    // Update existing subscription status
                    $localSub->update([
                        'stripe_status' => $stripeSub->status,
                        'ends_at' => $stripeSub->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at) : null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $synced > 0
                    ? "Synced {$synced} subscription(s) from Stripe."
                    : 'Subscription data is up to date.',
                'data' => [
                    'subscribed' => $user->fresh()->subscribed('default'),
                    'on_trial' => $user->fresh()->onTrial('default'),
                    'cancelled' => $user->fresh()->subscription('default')?->canceled() ?? false,
                    'on_grace_period' => $user->fresh()->subscription('default')?->onGracePeriod() ?? false,
                    'ends_at' => $user->fresh()->subscription('default')?->ends_at,
                    'subscription' => $user->fresh()->subscription('default'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not sync subscription: ' . $e->getMessage(),
            ], 500);
        }
    }
}
