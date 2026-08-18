<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MenuItemResource;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\QrScan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $today = QrScan::whereDate('scanned_at', today())->count();
        $week = QrScan::where('scanned_at', '>=', now()->subDays(7))->count();
        $month = QrScan::where('scanned_at', '>=', now()->subDays(30))->count();

        $missingPrice = MenuItem::whereNull('price')->count();
        $missingImage = MenuItem::whereNull('image')->count();

        return [
            Stat::make('Scans today', number_format($today))
                ->description($week.' in the last 7 days')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart($this->last7Days())
                ->color('success'),

            Stat::make('Scans, last 30 days', number_format($month))
                ->description('Menu opened from the QR code')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Menu items', number_format(MenuItem::count()))
                ->description(Category::count().' categories')
                ->descriptionIcon('heroicon-m-cake')
                ->color('gray'),

            Stat::make('Missing a price', number_format($missingPrice))
                ->description($missingPrice > 0 ? 'Shown as “—” on the menu' : 'All items priced')
                ->descriptionIcon('heroicon-m-banknotes')
                ->url(MenuItemResource::getUrl('index', ['tableFilters' => ['missing_price' => ['isActive' => true]]]))
                ->color($missingPrice > 0 ? 'warning' : 'success'),

            Stat::make('Missing a photo', number_format($missingImage))
                ->description($missingImage > 0 ? 'Using the placeholder' : 'All items have photos')
                ->descriptionIcon('heroicon-m-photo')
                ->url(MenuItemResource::getUrl('index', ['tableFilters' => ['missing_image' => ['isActive' => true]]]))
                ->color($missingImage > 0 ? 'warning' : 'success'),

            Stat::make('Public page', 'Open')
                ->description(parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url'))
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->url(route('landing'), shouldOpenInNewTab: true)
                ->color('primary'),
        ];
    }

    /** Scan counts for the last 7 days, oldest first, for the sparkline. */
    private function last7Days(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo) => QrScan::whereDate('scanned_at', today()->subDays($daysAgo))->count())
            ->all();
    }
}
