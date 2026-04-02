<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Models\Listing;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_review')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Listing Info')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_review' => 'Pending Review',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'expired' => 'Expired',
                            ])
                            ->required()
                            ->default('draft'),
                        Forms\Components\RichEditor::make('description')
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->collection('cover')
                            ->image()
                            ->maxSize(10240)
                            ->label('Cover Image')
                            ->helperText('Recommended: 1200x600px')
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->collection('gallery')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxSize(10240)
                            ->maxFiles(20)
                            ->label('Photo Gallery')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('address')->maxLength(255),
                        Forms\Components\TextInput::make('city')->required()->maxLength(255),
                        Forms\Components\TextInput::make('state')->required()->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')->required()->maxLength(10),
                        Forms\Components\TextInput::make('latitude')->numeric(),
                        Forms\Components\TextInput::make('longitude')->numeric(),
                    ])->columns(3),

                Forms\Components\Section::make('Contact')
                    ->schema([
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                        Forms\Components\TextInput::make('email')->email()->maxLength(255),
                        Forms\Components\TextInput::make('website')->url()->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Social Media')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('instagram_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('twitter_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('linkedin_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('yelp_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('video_url')->url()->maxLength(255),
                    ])->columns(3)->collapsed(),

                Forms\Components\Section::make('SEO & Keywords')
                    ->schema([
                        Forms\Components\Textarea::make('keywords'),
                        Forms\Components\TextInput::make('meta_description')->maxLength(255),
                    ])->collapsed(),

                Forms\Components\Section::make('Flags')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')->label('Featured'),
                        Forms\Components\Toggle::make('is_claimed')->label('Claimed'),
                        Forms\Components\Toggle::make('is_priority')->label('Priority Review')
                            ->helperText('Paid subscribers get priority listing review'),
                        Forms\Components\DateTimePicker::make('featured_until'),
                        Forms\Components\DateTimePicker::make('published_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'draft' => 'gray',
                        'pending_review' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                        'expired' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city')->searchable(),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
                Tables\Columns\IconColumn::make('is_claimed')->boolean()->label('Claimed'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_claimed')->label('Claimed'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Listing $record) => $record->status === 'pending_review')
                    ->action(function (Listing $record) {
                        $record->approve();
                        $record->user->notify(new ListingApproved($record));
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->placeholder('Optional reason for rejection'),
                    ])
                    ->visible(fn(Listing $record) => $record->status === 'pending_review')
                    ->action(function (Listing $record, array $data) {
                        $record->reject();
                        $record->user->notify(new ListingRejected($record, $data['reason'] ?? null));
                    }),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Listing $record) => $record->status === 'active')
                    ->action(fn(Listing $record) => $record->suspend()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function (Listing $record) {
                                if ($record->status === 'pending_review') {
                                    $record->approve();
                                    $record->user->notify(new ListingApproved($record));
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
