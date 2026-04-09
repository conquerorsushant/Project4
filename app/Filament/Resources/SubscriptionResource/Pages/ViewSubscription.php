<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Laravel\Cashier\Subscription;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncFromStripe')
                ->label('Sync from Stripe')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    try {
                        $record = $this->getRecord();
                        $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));
                        $stripeSub = $stripeClient->subscriptions->retrieve($record->stripe_id);

                        $record->update([
                            'stripe_status' => $stripeSub->status,
                            'stripe_price' => $stripeSub->items->data[0]->price->id ?? $record->stripe_price,
                            'quantity' => $stripeSub->items->data[0]->quantity ?? $record->quantity,
                            'current_period_end' => !empty($stripeSub->current_period_end)
                                ? \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end)
                                : null,
                            'ends_at' => $stripeSub->cancel_at
                                ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at)
                                : null,
                            'trial_ends_at' => $stripeSub->trial_end
                                ? \Carbon\Carbon::createFromTimestamp($stripeSub->trial_end)
                                : null,
                        ]);

                        $this->refreshFormData([
                            'stripe_status',
                            'stripe_price',
                            'quantity',
                            'current_period_end',
                            'ends_at',
                            'trial_ends_at',
                        ]);

                        Notification::make()
                            ->title('Subscription synced successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('viewInStripe')
                ->label('View in Stripe')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn() => 'https://dashboard.stripe.com/subscriptions/' . $this->getRecord()->stripe_id)
                ->openUrlInNewTab(),
        ];
    }
}
