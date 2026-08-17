<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Generous enough that a table of diners refreshing is never blocked.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));

        // Any admin edit to the menu invalidates the cached public payload.
        foreach ([Category::class, MenuItem::class] as $model) {
            $model::saved(fn () => RestaurantSetting::flushCache());
            $model::deleted(fn () => RestaurantSetting::flushCache());
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
