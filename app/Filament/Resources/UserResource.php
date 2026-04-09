<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Laravel\Cashier\Subscription;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->options([
                                'user' => 'User',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('user'),
                    ])->columns(2),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('city')->maxLength(255),
                        Forms\Components\TextInput::make('state')->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')->maxLength(10),
                    ])->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_banned')
                            ->label('Banned'),
                        Forms\Components\DateTimePicker::make('banned_at')
                            ->visible(fn($get) => $get('is_banned')),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ])->columns(2),

                Forms\Components\Section::make('Login Activity')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Last Login')
                            ->disabled(),
                        Forms\Components\TextInput::make('last_login_ip')
                            ->label('Last Login IP')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'admin' => 'danger',
                        'user' => 'primary',
                    }),
                Tables\Columns\IconColumn::make('is_banned')
                    ->boolean()
                    ->label('Banned'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->boolean()
                    ->label('Verified')
                    ->getStateUsing(fn($record) => $record->email_verified_at !== null),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Subscription')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $sub = $record->subscriptions()->latest()->first();
                        if (!$sub) return 'none';
                        if ($sub->stripe_status === 'active' && $sub->ends_at) return 'cancelling';
                        return $sub->stripe_status;
                    })
                    ->color(fn(string $state) => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'cancelling' => 'warning',
                        'past_due' => 'warning',
                        'canceled', 'incomplete_expired' => 'danger',
                        'none' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_payment')
                    ->label('Next Payment')
                    ->getStateUsing(function ($record) {
                        $sub = $record->subscriptions()
                            ->where('stripe_status', 'active')
                            ->latest()
                            ->first();
                        return $sub?->current_period_end;
                    })
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('listings_count')
                    ->counts('listings')
                    ->label('Listings'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_login_ip')
                    ->label('Login IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                    ]),
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Banned'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
                Tables\Filters\Filter::make('subscribed')
                    ->label('Has Active Subscription')
                    ->query(fn($query) => $query->whereHas('subscriptions', fn($q) => $q->where('stripe_status', 'active'))),
                Tables\Filters\Filter::make('not_subscribed')
                    ->label('No Active Subscription')
                    ->query(fn($query) => $query->whereDoesntHave('subscriptions', fn($q) => $q->where('stripe_status', 'active'))),
                Tables\Filters\Filter::make('logged_in_recently')
                    ->label('Logged In (Last 7 Days)')
                    ->query(fn($query) => $query->where('last_login_at', '>=', now()->subDays(7))),
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Never Logged In')
                    ->query(fn($query) => $query->whereNull('last_login_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(User $record) => !$record->is_banned && !$record->isAdmin())
                    ->action(fn(User $record) => $record->ban()),
                Tables\Actions\Action::make('unban')
                    ->label('Unban')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(User $record) => $record->is_banned)
                    ->action(fn(User $record) => $record->unban()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
