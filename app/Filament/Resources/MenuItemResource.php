<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Services\ImageProcessor;
use App\Models\Category;
use App\Models\MenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationGroup = 'Menu';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->columnSpan(2)
                ->schema([
                    Forms\Components\Section::make('Names & descriptions')
                        ->description('Arabic and English side by side. Anything left blank falls back to the other language on the public menu.')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name_ar')
                                ->label('Name (Arabic)')
                                ->maxLength(255)
                                ->extraInputAttributes(['dir' => 'rtl']),

                            Forms\Components\TextInput::make('name_en')
                                ->label('Name (English)')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description_ar')
                                ->label('Description (Arabic)')
                                ->rows(4)
                                ->extraInputAttributes(['dir' => 'rtl']),

                            Forms\Components\Textarea::make('description_en')
                                ->label('Description (English)')
                                ->rows(4),
                        ]),
                ]),

            Forms\Components\Group::make()
                ->columnSpan(1)
                ->schema([
                    Forms\Components\Section::make('Details')->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name_en')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(fn () => settings('currency'))
                            ->helperText('Leave empty if the item has no fixed price — the menu shows “—”.'),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('spicy, vegetarian…'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->default(0),
                    ]),

                    Forms\Components\Section::make('Image')->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Photo')
                            ->image()
                            ->disk('public')
                            ->directory('menu-items')
                            ->saveUploadedFileUsing(fn ($file) => ImageProcessor::handleUpload($file, 'menu-items'))
                            ->imageEditor()
                            ->maxSize(3072)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('JPG, PNG or WebP, up to 3 MB. Resized to 1200px and converted to WebP on save.'),
                    ]),

                    Forms\Components\Section::make('Availability')->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Available')
                            ->default(true)
                            ->helperText('Off = greyed out and marked “Not available”.'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Chef’s pick')
                            ->default(false),
                    ]),
                ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->groups([
                Tables\Grouping\Group::make('category.name_en')->label('Category')->collapsible(),
            ])
            ->defaultGroup('category.name_en')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('English')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MenuItem $r) => str($r->description_en ?? '')->limit(60)),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Arabic')
                    ->searchable()
                    ->extraAttributes(['dir' => 'rtl'])
                    ->placeholder('— missing —'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' '.settings('currency'))
                    ->sortable()
                    ->placeholder('— missing —'),

                Tables\Columns\IconColumn::make('is_available')->label('Available')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->label('Pick')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::orderBy('sort_order')->pluck('name_en', 'id')),

                Tables\Filters\TernaryFilter::make('is_available')->label('Available'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Chef’s pick'),

                Tables\Filters\Filter::make('missing_price')
                    ->label('Missing price')
                    ->query(fn ($q) => $q->whereNull('price')),

                Tables\Filters\Filter::make('missing_image')
                    ->label('Missing image')
                    ->query(fn ($q) => $q->whereNull('image')),

                Tables\Filters\Filter::make('missing_arabic')
                    ->label('Missing Arabic name')
                    ->query(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', '')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('available')
                        ->label('Mark available')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_available' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('unavailable')
                        ->label('Mark unavailable')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_available' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as chef’s pick')
                        ->icon('heroicon-o-star')
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
