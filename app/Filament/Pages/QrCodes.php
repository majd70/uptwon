<?php

namespace App\Filament\Pages;

use App\Services\QrCodeBuilder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One QR code for the whole restaurant. Per-table codes were dropped; the
 * builder and the qr_scans.table_number column still understand them, so the
 * feature can come back without a migration.
 */
class QrCodes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'QR code';

    protected static ?string $navigationLabel = 'QR code';

    protected static string $view = 'filament.pages.qr-codes';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['with_logo' => false]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Your QR code')
                    ->description('Opens the menu. Print it for the door, the window, or a card on each table.')
                    ->schema([
                        Forms\Components\Toggle::make('with_logo')
                            ->label('Put the logo in the centre')
                            ->helperText(fn () => settings('logo')
                                ? 'The code still scans — it is generated at the highest error-correction level.'
                                : 'Upload a logo under Settings → Branding first.')
                            ->disabled(fn () => ! settings('logo'))
                            ->live(),
                    ]),
            ]);
    }

    /** Data URI so the preview needs no extra request or temp file. */
    public function getPreviewProperty(): string
    {
        $png = app(QrCodeBuilder::class)->png(null, 600, $this->withLogo());

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function getTargetUrlProperty(): string
    {
        return QrCodeBuilder::url();
    }

    public function downloadPng(): StreamedResponse
    {
        $withLogo = $this->withLogo();

        return Response::streamDownload(
            fn () => print (app(QrCodeBuilder::class)->png(null, 1024, $withLogo)),
            'uptown-qr.png',
            ['Content-Type' => 'image/png'],
        );
    }

    public function downloadSvg(): StreamedResponse
    {
        $withLogo = $this->withLogo();

        return Response::streamDownload(
            fn () => print (app(QrCodeBuilder::class)->svg(null, 1024, $withLogo)),
            'uptown-qr.svg',
            ['Content-Type' => 'image/svg+xml'],
        );
    }

    private function withLogo(): bool
    {
        return (bool) ($this->form->getState()['with_logo'] ?? false);
    }
}
