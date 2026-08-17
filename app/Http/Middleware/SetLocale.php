<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale from, in order: an explicit ?lang= switch, the
 * visitor's cookie, then the restaurant's configured default. An explicit
 * switch is persisted back to the cookie for a year.
 */
class SetLocale
{
    public const COOKIE = 'uptown_locale';

    public const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        // The admin panel is authored in English; letting a visitor's Arabic
        // cookie flip it to RTL would mix Arabic chrome with English labels.
        if ($request->is('admin', 'admin/*', 'livewire/*')) {
            app()->setLocale('en');

            return $next($request);
        }

        $requested = $request->query('lang');
        $explicit = in_array($requested, self::SUPPORTED, true) ? $requested : null;

        $cookie = $request->cookie(self::COOKIE);
        $cookie = in_array($cookie, self::SUPPORTED, true) ? $cookie : null;

        $default = settings('default_locale');
        $default = in_array($default, self::SUPPORTED, true) ? $default : 'ar';

        $locale = $explicit ?? $cookie ?? $default;
        app()->setLocale($locale);

        $response = $next($request);

        if ($explicit !== null && $explicit !== $cookie) {
            $response->headers->setCookie(
                Cookie::make(self::COOKIE, $explicit, minutes: 60 * 24 * 365)
            );
        }

        return $response;
    }
}
