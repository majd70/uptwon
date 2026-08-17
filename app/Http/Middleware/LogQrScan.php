<?php

namespace App\Http\Middleware;

use App\Models\QrScan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a row in qr_scans when the landing page is opened with tracking
 * parameters, e.g. /?utm_source=qr&table=5.
 */
class LogQrScan
{
    public function handle(Request $request, Closure $next): Response
    {
        $source = $request->query('utm_source');
        $table = $request->query('table');

        if (filled($source) || filled($table)) {
            QrScan::create([
                'scanned_at' => now(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'utm_source' => is_string($source) ? mb_substr($source, 0, 100) : null,
                'table_number' => is_scalar($table) ? mb_substr((string) $table, 0, 20) : null,
            ]);
        }

        return $next($request);
    }
}
