<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestListings extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Listings';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Listing::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'draft' => 'gray',
                        'pending_review' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                        'expired' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
