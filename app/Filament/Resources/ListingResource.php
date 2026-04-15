<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Models\Listing;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\HtmlString;

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

                Forms\Components\Section::make('Services Offered')
                    ->description('Add the services this business provides')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Forms\Components\TagsInput::make('services')
                            ->label('Services')
                            ->placeholder('Type a service and press enter')
                            ->helperText('Add services one at a time. Type and press Enter or comma to add each service.')
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Business Hours')
                    ->description('Set the weekly operating schedule')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Repeater::make('operating_hours')
                            ->label('Weekly Schedule')
                            ->schema([
                                Forms\Components\Select::make('day')
                                    ->label('Day')
                                    ->options([
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday',
                                    ])
                                    ->required()
                                    ->distinct(),
                                Forms\Components\TimePicker::make('open')
                                    ->label('Opens at')
                                    ->seconds(false)
                                    ->required(fn(Get $get): bool => ! $get('closed'))
                                    ->disabled(fn(Get $get): bool => (bool) $get('closed'))
                                    ->dehydrated(),
                                Forms\Components\TimePicker::make('close')
                                    ->label('Closes at')
                                    ->seconds(false)
                                    ->required(fn(Get $get): bool => ! $get('closed'))
                                    ->disabled(fn(Get $get): bool => (bool) $get('closed'))
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('closed')
                                    ->label('Closed')
                                    ->inline(false)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $set('open', null);
                                            $set('close', null);
                                        }
                                    }),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('Add Day')
                            ->itemLabel(fn(array $state): ?string => ucfirst($state['day'] ?? 'New day'))
                            ->collapsible()
                            ->cloneable()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('hide_business_hours')
                            ->label('Hide Business Hours from Public View')
                            ->helperText('When enabled, business hours will not be displayed on the public listing page')
                            ->onIcon('heroicon-o-eye-slash')
                            ->offIcon('heroicon-o-eye'),
                    ]),

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
                Tables\Columns\TextColumn::make('slug')
                    ->label('View')
                    ->formatStateUsing(fn(Listing $record) => $record->status === 'active'
                        ? new HtmlString('<span style="color:rgb(59 130 246);text-decoration:underline;cursor:pointer;">View</span>')
                        : new HtmlString('<span style="color:rgb(156 163 175);">View</span>'))
                    ->html()
                    ->url(fn(Listing $record) => $record->status === 'active'
                        ? 'https://needanestimate.com/listing/' . $record->slug
                        : null)
                    ->openUrlInNewTab(),
                Tables\Columns\IconColumn::make('hide_business_hours')
                    ->boolean()
                    ->label('Hours Hidden')
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TernaryFilter::make('has_services')
                    ->label('Has Services')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('services')->where('services', '!=', '[]'),
                        false: fn($query) => $query->where(fn($q) => $q->whereNull('services')->orWhere('services', '[]')),
                    ),
                Tables\Filters\TernaryFilter::make('has_hours')
                    ->label('Has Hours')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('operating_hours')->where('operating_hours', '!=', '[]'),
                        false: fn($query) => $query->where(fn($q) => $q->whereNull('operating_hours')->orWhere('operating_hours', '[]')),
                    ),
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
