<?php

namespace App\Console;

use App\Models\Listing;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Expire featured listings whose featured_until date has passed
        $schedule->call(function () {
            Listing::where('is_featured', true)
                ->whereNotNull('featured_until')
                ->where('featured_until', '<', now())
                ->update([
                    'is_featured' => false,
                    'featured_until' => null,
                ]);
        })->daily()->name('expire-featured-listings');

        // Expire listings whose subscription has lapsed (check Cashier)
        $schedule->call(function () {
            Listing::where('status', 'active')
                ->whereHas('user', function ($q) {
                    $q->whereDoesntHave('subscriptions', function ($sq) {
                        $sq->where('stripe_status', 'active');
                    });
                })
                ->update(['status' => 'expired']);
        })->daily()->name('expire-unpaid-listings');

        // Sync subscription billing data from Stripe (next payment dates, etc.)
        $schedule->command('subscriptions:sync')->twiceDaily(6, 18)->name('sync-subscriptions');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
