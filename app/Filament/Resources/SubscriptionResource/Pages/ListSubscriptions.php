<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncAllFromStripe')
                ->label('Sync All Active from Stripe')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sync All Active Subscriptions')
                ->modalDescription('This will fetch the latest billing data from Stripe for all active subscriptions. This may take a moment.')
                ->action(function () {
                    $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));
                    $subscriptions = \Laravel\Cashier\Subscription::where('stripe_status', 'active')->get();
                    $synced = 0;
                    $failed = 0;

                    foreach ($subscriptions as $sub) {
                        try {
                            $stripeSub = $stripeClient->subscriptions->retrieve($sub->stripe_id);
                            $sub->update([
                                'stripe_status' => $stripeSub->status,
                                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end),
                                'ends_at' => $stripeSub->cancel_at
                                    ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at)
                                    : null,
                            ]);
                            $synced++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title("Synced {$synced} subscription(s)" . ($failed ? ", {$failed} failed" : ''))
                        ->success()
                        ->send();
                }),
        ];
    }
}
