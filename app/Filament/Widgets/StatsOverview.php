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
        $activeSubscriptions = Subscription::query()->where('stripe_status', 'active')->count();

        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Active Listings', Listing::where('status', 'active')->count())
                ->description(Listing::where('status', 'pending_review')->count() . ' pending review')
                ->icon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make('Active Subscriptions', $activeSubscriptions)
                ->description('Recurring plans')
                ->icon('heroicon-o-credit-card')
                ->color('warning'),

            Stat::make('Pending Reviews', Review::where('is_approved', false)->count())
                ->description(Review::where('is_approved', true)->count() . ' approved')
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
