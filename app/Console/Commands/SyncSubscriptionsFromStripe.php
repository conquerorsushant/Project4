<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

class SyncSubscriptionsFromStripe extends Command
{
    protected $signature = 'subscriptions:sync {--status=active : Stripe status to sync (active, all)}';

    protected $description = 'Sync subscription billing data (next payment date, status) from Stripe';

    public function handle(): int
    {
        $stripeSecret = config('cashier.secret');

        if (!$stripeSecret) {
            $this->error('Stripe secret key is not configured.');
            return self::FAILURE;
        }

        $stripeClient = new \Stripe\StripeClient($stripeSecret);

        $statusFilter = $this->option('status');
        $query = Subscription::query();

        if ($statusFilter !== 'all') {
            $query->where('stripe_status', $statusFilter);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions to sync.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $stripeSub = $stripeClient->subscriptions->retrieve($subscription->stripe_id);

                $subscription->update([
                    'stripe_status' => $stripeSub->status,
                    'stripe_price' => $stripeSub->items->data[0]->price->id ?? $subscription->stripe_price,
                    'quantity' => $stripeSub->items->data[0]->quantity ?? $subscription->quantity,
                    'current_period_end' => Carbon::createFromTimestamp($stripeSub->current_period_end),
                    'ends_at' => $stripeSub->cancel_at
                        ? Carbon::createFromTimestamp($stripeSub->cancel_at)
                        : null,
                    'trial_ends_at' => $stripeSub->trial_end
                        ? Carbon::createFromTimestamp($stripeSub->trial_end)
                        : null,
                ]);

                $synced++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->warn("Failed to sync subscription {$subscription->stripe_id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Synced: {$synced} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
