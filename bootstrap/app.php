<?php

use App\Http\Middleware\LogQrScan;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SetLocale runs for every web request, including the Filament panel,
        // so the admin follows the same language cookie as the public site.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'qr.scan' => LogQrScan::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
