<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Laravel\Cashier\Subscription;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Subscriptions';

    public static function getNavigationBadge(): ?string
    {
        return (string) Subscription::where('stripe_status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Subscription Details')
                ->schema([
                    Forms\Components\TextInput::make('type')
                        ->disabled(),
                    Forms\Components\TextInput::make('stripe_id')
                        ->label('Stripe Subscription ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('stripe_status')
                        ->disabled(),
                    Forms\Components\TextInput::make('stripe_price')
                        ->label('Price ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('quantity')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('trial_ends_at')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('current_period_end')
                        ->label('Next Payment Date')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_price')
                    ->label('Price ID')
                    ->toggleable()
                    ->limit(25)
                    ->tooltip(fn($state) => $state),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Cancels At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn($state) => $state ? 'danger' : null),
                Tables\Columns\TextColumn::make('current_period_end')
                    ->label('Next Payment')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not synced')
                    ->color(fn($record) => $record->current_period_end && $record->current_period_end->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subscribed On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'incomplete' => 'Incomplete',
                        'incomplete_expired' => 'Incomplete Expired',
                        'paused' => 'Paused',
                    ]),
                Tables\Filters\Filter::make('has_trial')
                    ->label('On Trial')
                    ->query(fn($query) => $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())),
                Tables\Filters\Filter::make('cancelled')
                    ->label('Cancelled (Grace Period)')
                    ->query(fn($query) => $query->whereNotNull('ends_at')->where('ends_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('syncFromStripe')
                    ->label('Sync from Stripe')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Subscription from Stripe')
                    ->modalDescription('This will fetch the latest data from Stripe and update the local record.')
                    ->action(function (Subscription $record) {
                        try {
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
                Tables\Actions\Action::make('viewInStripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn(Subscription $record) => 'https://dashboard.stripe.com/subscriptions/' . $record->stripe_id)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('syncAll')
                    ->label('Sync Selected from Stripe')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $stripeClient = new \Stripe\StripeClient(config('cashier.secret'));
                        $synced = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            try {
                                $stripeSub = $stripeClient->subscriptions->retrieve($record->stripe_id);
                                $record->update([
                                    'stripe_status' => $stripeSub->status,
                                    'current_period_end' => !empty($stripeSub->current_period_end)
                                        ? \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end)
                                        : null,
                                    'ends_at' => $stripeSub->cancel_at
                                        ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at)
                                        : null,
                                ]);
                                $synced++;
                            } catch (\Exception $e) {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title("Synced {$synced} subscription(s)" . ($failed ? ", {$failed} failed" : ''))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('Phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('user.city')
                            ->label('City')
                            ->placeholder('—'),
                    ])->columns(4),

                Infolists\Components\Section::make('Subscription Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('stripe_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'active' => 'success',
                                'trialing' => 'info',
                                'past_due' => 'warning',
                                'canceled', 'incomplete_expired' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('stripe_price')
                            ->label('Price ID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('stripe_id')
                            ->label('Stripe Subscription ID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('quantity'),
                    ])->columns(3),

                Infolists\Components\Section::make('Billing Schedule')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Subscribed Since')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('current_period_end')
                            ->label('Next Payment Date')
                            ->dateTime()
                            ->placeholder('Not synced — click "Sync from Stripe"')
                            ->color(fn($state) => $state && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->label('Trial Ends At')
                            ->dateTime()
                            ->placeholder('No trial'),
                        Infolists\Components\TextEntry::make('ends_at')
                            ->label('Cancellation Date')
                            ->dateTime()
                            ->placeholder('Not cancelled')
                            ->color('danger'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }
}
