<?php

namespace App\Filament\Pages;

use App\Models\RestaurantSetting;
use App\Services\ImageProcessor;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Single-record settings screen backed by the restaurant_settings row.
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Restaurant settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(RestaurantSetting::current()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make()->columnSpanFull()->tabs([

                    Forms\Components\Tabs\Tab::make('Branding')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('Restaurant name (Arabic)')
                                    ->required()
                                    ->extraInputAttributes(['dir' => 'rtl']),

                                Forms\Components\TextInput::make('name_en')
                                    ->label('Restaurant name (English)')
                                    ->required(),

                                Forms\Components\TextInput::make('tagline_ar')
                                    ->label('Tagline (Arabic)')
                                    ->extraInputAttributes(['dir' => 'rtl']),

                                Forms\Components\TextInput::make('tagline_en')
                                    ->label('Tagline (English)'),

                                Forms\Components\TextInput::make('monogram')
                                    ->label('Monogram')
                                    ->maxLength(8)
                                    ->placeholder('UT')
                                    ->helperText('The letters drawn inside the gold ring. Ignored once you upload a logo.'),

                                Forms\Components\FileUpload::make('logo')
                                    ->label('Logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding')
                                    ->saveUploadedFileUsing(fn ($file) => ImageProcessor::handleUpload($file, 'branding'))
                                    ->imageEditor()
                                    ->maxSize(3072)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Replaces the monogram. Shown inside the gold ring — square, transparent PNG works best.'),

                                Forms\Components\FileUpload::make('cover_image')
                                    ->label('Cover / share image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding')
                                    ->saveUploadedFileUsing(fn ($file) => ImageProcessor::handleUpload($file, 'branding'))
                                    ->imageEditor()
                                    ->maxSize(3072)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Used when the link is shared on WhatsApp or social media.'),

                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary colour')
                                    ->required()
                                    ->helperText('Buttons, headings and the menu accent.'),

                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->label('Text / light colour')
                                    ->required()
                                    ->helperText('The cream used for text on the dark background.'),

                                Forms\Components\ColorPicker::make('accent_color')
                                    ->label('Accent colour')
                                    ->required()
                                    ->helperText('Brass. Rules, icons and the “View menu” button.'),
                            ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Contact')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->helperText('Shown as a tap-to-call button.'),

                                Forms\Components\TextInput::make('whatsapp')
                                    ->label('WhatsApp number')
                                    ->tel()
                                    ->helperText('International format, e.g. +201234567890.'),

                                Forms\Components\TextInput::make('google_maps_url')
                                    ->label('Google Maps link')
                                    ->url()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('address_ar')
                                    ->label('Address (Arabic)')
                                    ->extraInputAttributes(['dir' => 'rtl']),

                                Forms\Components\TextInput::make('address_en')
                                    ->label('Address (English)'),
                            ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Social')
                        ->icon('heroicon-o-share')
                        ->schema([
                            Forms\Components\TextInput::make('instagram_url')->label('Instagram URL')->url(),
                            Forms\Components\TextInput::make('facebook_url')->label('Facebook URL')->url(),
                            Forms\Components\TextInput::make('tiktok_url')->label('TikTok URL')->url(),
                            Forms\Components\Placeholder::make('social_note')
                                ->label('')
                                ->content('Links you leave empty are hidden from the landing page.'),
                        ]),

                    Forms\Components\Tabs\Tab::make('Hours & locale')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Forms\Components\Repeater::make('working_hours')
                                ->label('Working hours')
                                ->columns(3)
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state) => $state['label_en'] ?? $state['label_ar'] ?? 'New row')
                                ->schema([
                                    Forms\Components\TextInput::make('label_ar')
                                        ->label('Days (Arabic)')
                                        ->extraInputAttributes(['dir' => 'rtl']),
                                    Forms\Components\TextInput::make('label_en')
                                        ->label('Days (English)'),
                                    Forms\Components\TextInput::make('hours')
                                        ->label('Hours')
                                        ->placeholder('12:00 – 02:00'),
                                ]),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('currency')
                                    ->label('Currency code')
                                    ->required()
                                    ->maxLength(8),

                                Forms\Components\Select::make('default_locale')
                                    ->label('Default language')
                                    ->required()
                                    ->options(['ar' => 'العربية (Arabic)', 'en' => 'English'])
                                    ->helperText('Used for first-time visitors who have not picked a language.'),
                            ]),
                        ]),
                ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = RestaurantSetting::current();
        // current() may hand back an unsaved model on a fresh install
        $settings->exists ? $settings->update($data) : RestaurantSetting::create($data);

        RestaurantSetting::flushCache();

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('The public page has been updated.')
            ->send();
    }
}
