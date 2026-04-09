<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AdminPaymentReceived;
use App\Notifications\PaymentSuccessNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Subscription;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer subscription updated event.
     * Extends Cashier's handler to also sync current_period_end.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $data = $payload['data']['object'] ?? [];

        if (!empty($data['id']) && !empty($data['current_period_end'])) {
            Subscription::where('stripe_id', $data['id'])->update([
                'current_period_end' => Carbon::createFromTimestamp($data['current_period_end']),
            ]);
        }
    }

    /**
     * Handle customer subscription created event.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        parent::handleCustomerSubscriptionCreated($payload);

        $data = $payload['data']['object'] ?? [];

        if (!empty($data['id']) && !empty($data['current_period_end'])) {
            Subscription::where('stripe_id', $data['id'])->update([
                'current_period_end' => Carbon::createFromTimestamp($data['current_period_end']),
            ]);
        }
    }

    /**
     * Handle invoice payment succeeded — update billing date & send notifications.
     */
    protected function handleInvoicePaymentSucceeded(array $payload): void
    {
        parent::handleInvoicePaymentSucceeded($payload);

        $data = $payload['data']['object'] ?? [];
        $subscriptionId = $data['subscription'] ?? null;
        $customerId = $data['customer'] ?? null;

        // Sync billing period
        if ($subscriptionId) {
            try {
                $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));
                $stripeSub = $stripeClient->subscriptions->retrieve($subscriptionId);

                Subscription::where('stripe_id', $subscriptionId)->update([
                    'current_period_end' => Carbon::createFromTimestamp($stripeSub->current_period_end),
                ]);
            } catch (\Exception $e) {
                // Silently fail — admin can manually sync
            }
        }

        // Send payment confirmation emails
        if ($customerId) {
            try {
                $user = User::where('stripe_id', $customerId)->first();
                $amountPaid = isset($data['amount_paid'])
                    ? '$' . number_format($data['amount_paid'] / 100, 2)
                    : 'N/A';
                $invoiceId = $data['id'] ?? 'N/A';

                // Notify the user
                if ($user) {
                    $user->notify(new PaymentSuccessNotification($amountPaid, $invoiceId));
                }

                // Notify admin
                $adminEmail = config('app.admin_email');
                if ($adminEmail) {
                    Notification::route('mail', $adminEmail)->notify(
                        new AdminPaymentReceived(
                            $user?->name ?? 'Unknown',
                            $user?->email ?? $customerId,
                            $amountPaid,
                            $invoiceId,
                        )
                    );
                }
            } catch (\Exception $e) {
                // Don't let notification failures break webhook processing
            }
        }
    }
}
