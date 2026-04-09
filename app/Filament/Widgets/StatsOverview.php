<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Cashier\Subscription;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::where('stripe_status', 'active')->count();
        $cancellingSubscriptions = Subscription::where('stripe_status', 'active')
            ->whereNotNull('ends_at')
            ->count();
        $trialingSubscriptions = Subscription::where('stripe_status', 'trialing')->count();
        $pastDueSubscriptions = Subscription::where('stripe_status', 'past_due')->count();

        $recentLogins = User::where('last_login_at', '>=', now()->subDays(7))->count();
        $neverLoggedIn = User::whereNull('last_login_at')
            ->where('role', '!=', 'admin')
            ->count();

        $subscriptionDesc = $cancellingSubscriptions > 0
            ? "{$cancellingSubscriptions} cancelling"
            : ($trialingSubscriptions > 0 ? "{$trialingSubscriptions} trialing" : 'Recurring plans');

        if ($pastDueSubscriptions > 0) {
            $subscriptionDesc .= " · {$pastDueSubscriptions} past due";
        }

        return [
            Stat::make('Total Users', User::count())
                ->description("{$recentLogins} active this week" . ($neverLoggedIn > 0 ? " · {$neverLoggedIn} never logged in" : ''))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Active Listings', Listing::where('status', 'active')->count())
                ->description(Listing::where('status', 'pending_review')->count() . ' pending review')
                ->icon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make('Active Subscriptions', $activeSubscriptions)
                ->description($subscriptionDesc)
                ->icon('heroicon-o-credit-card')
                ->color($pastDueSubscriptions > 0 ? 'danger' : 'warning'),

            Stat::make('Pending Reviews', Review::where('is_approved', false)->count())
                ->description(Review::where('is_approved', true)->count() . ' approved')
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
