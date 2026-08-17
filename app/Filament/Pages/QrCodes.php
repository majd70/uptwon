<?php

namespace App\Filament\Pages;

use App\Services\QrCodeBuilder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class QrCodes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'QR codes';

    protected static ?string $navigationLabel = 'QR codes';

    protected static string $view = 'filament.pages.qr-codes';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'table' => null,
            'with_logo' => false,
            'tables_from' => 1,
            'tables_to' => 10,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Single code')
                    ->description('Leave the table number empty for the general code you put on the door or the window.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('table')
                            ->label('Table number')
                            ->placeholder('e.g. 5 — or leave empty')
                            ->maxLength(20)
                            ->live(debounce: 400),

                        Forms\Components\Toggle::make('with_logo')
                            ->label('Put the logo in the centre')
                            ->helperText(fn () => settings('logo') ? null : 'Upload a logo in Settings first.')
                            ->disabled(fn () => ! settings('logo'))
                            ->live(),
                    ]),

                Forms\Components\Section::make('Table codes (bulk)')
                    ->description('Generates one PNG per table, delivered as a ZIP.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('tables_from')
                            ->label('From table')
                            ->numeric()->minValue(1)->maxValue(999)->required(),

                        Forms\Components\TextInput::make('tables_to')
                            ->label('To table')
                            ->numeric()->minValue(1)->maxValue(999)->required(),
                    ]),
            ]);
    }

    /** Data URI so the preview needs no extra request or temp file. */
    public function getPreviewProperty(): string
    {
        $state = $this->form->getState();

        $png = app(QrCodeBuilder::class)->png(
            $state['table'] ?? null,
            600,
            (bool) ($state['with_logo'] ?? false),
        );

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function getTargetUrlProperty(): string
    {
        return QrCodeBuilder::url($this->form->getState()['table'] ?? null);
    }

    public function downloadPng(): StreamedResponse
    {
        $state = $this->form->getState();
        $table = $state['table'] ?? null;

        return Response::streamDownload(
            fn () => print (app(QrCodeBuilder::class)->png($table, 1024, (bool) ($state['with_logo'] ?? false))),
            $this->fileName($table, 'png'),
            ['Content-Type' => 'image/png'],
        );
    }

    public function downloadSvg(): StreamedResponse
    {
        $state = $this->form->getState();
        $table = $state['table'] ?? null;

        return Response::streamDownload(
            fn () => print (app(QrCodeBuilder::class)->svg($table, 1024, (bool) ($state['with_logo'] ?? false))),
            $this->fileName($table, 'svg'),
            ['Content-Type' => 'image/svg+xml'],
        );
    }

    public function downloadTableZip(): ?StreamedResponse
    {
        $state = $this->form->getState();
        $from = (int) $state['tables_from'];
        $to = (int) $state['tables_to'];

        if ($to < $from) {
            Notification::make()->danger()->title('“To” must not be smaller than “From”.')->send();

            return null;
        }

        if (($to - $from) > 199) {
            Notification::make()->danger()->title('Please request 200 tables or fewer at a time.')->send();

            return null;
        }

        $builder = app(QrCodeBuilder::class);
        $withLogo = (bool) ($state['with_logo'] ?? false);

        $tmp = tempnam(sys_get_temp_dir(), 'qr').'.zip';
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        for ($n = $from; $n <= $to; $n++) {
            $zip->addFromString("table-{$n}.png", $builder->png((string) $n, 1024, $withLogo));
        }

        $zip->close();

        return Response::streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, 'uptown-table-qr-codes.zip', ['Content-Type' => 'application/zip']);
    }

    private function fileName(?string $table, string $ext): string
    {
        return filled($table)
            ? "uptown-qr-table-{$table}.{$ext}"
            : "uptown-qr.{$ext}";
    }
}
