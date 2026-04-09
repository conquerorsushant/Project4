<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Laravel\Cashier\Subscription;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Subscriptions & Billing';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled', 'incomplete_expired' => 'danger',
                        'incomplete' => 'warning',
                        'paused' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stripe_price')
                    ->label('Plan / Price ID')
                    ->limit(30)
                    ->tooltip(fn($state) => $state),
                Tables\Columns\TextColumn::make('quantity')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_period_end')
                    ->label('Next Payment')
                    ->dateTime()
                    ->placeholder('Not synced')
                    ->color(fn($record) => $record->current_period_end && $record->current_period_end->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Cancels At')
                    ->dateTime()
                    ->placeholder('—')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('syncFromStripe')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Subscription $record) {
                        try {
                            $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));
                            $stripeSub = $stripeClient->subscriptions->retrieve($record->stripe_id);

                            $record->update([
                                'stripe_status' => $stripeSub->status,
                                'stripe_price' => $stripeSub->items->data[0]->price->id ?? $record->stripe_price,
                                'quantity' => $stripeSub->items->data[0]->quantity ?? $record->quantity,
                                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end),
                                'ends_at' => $stripeSub->cancel_at
                                    ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at)
                                    : null,
                                'trial_ends_at' => $stripeSub->trial_end
                                    ? \Carbon\Carbon::createFromTimestamp($stripeSub->trial_end)
                                    : null,
                            ]);

                            Notification::make()
                                ->title('Subscription synced')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync failed: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('viewInStripe')
                    ->label('Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn(Subscription $record) => 'https://dashboard.stripe.com/subscriptions/' . $record->stripe_id)
                    ->openUrlInNewTab(),
            ]);
    }
}
