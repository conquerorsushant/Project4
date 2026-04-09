<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Payment History';

    protected static ?string $title = 'Payment History & Transactions';

    protected static string $view = 'filament.pages.payment-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereNotNull('stripe_id')
                    ->whereHas('subscriptions')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_id')
                    ->label('Stripe Customer')
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('pm_type')
                    ->label('Payment Method')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pm_last_four')
                    ->label('Card Last 4')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Subscriptions')
                    ->counts('subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_invoices')
                    ->label('View Invoices')
                    ->icon('heroicon-o-document-text')
                    ->modalHeading(fn(User $record) => "Invoices for {$record->name}")
                    ->modalContent(function (User $record) {
                        if (!$record->hasStripeId()) {
                            return view('filament.pages.partials.no-invoices');
                        }

                        try {
                            $invoices = $record->invoices()->map(function ($invoice) {
                                return [
                                    'id' => $invoice->id,
                                    'date' => $invoice->date()->toFormattedDateString(),
                                    'total' => $invoice->total(),
                                    'status' => $invoice->status ?? 'unknown',
                                    'description' => $invoice->lines->first()?->description ?? 'Subscription',
                                    'invoice_pdf' => $invoice->invoice_pdf,
                                ];
                            });
                        } catch (\Exception $e) {
                            $invoices = collect();
                        }

                        return view('filament.pages.partials.invoices-list', [
                            'invoices' => $invoices,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('view_in_stripe')
                    ->label('Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(User $record) => "https://dashboard.stripe.com/customers/{$record->stripe_id}")
                    ->openUrlInNewTab()
                    ->visible(fn(User $record) => $record->stripe_id),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_active_subscription')
                    ->label('Active Subscription')
                    ->query(fn(Builder $query) => $query->whereHas('subscriptions', function ($q) {
                        $q->where('stripe_status', 'active');
                    })),
            ]);
    }
}
