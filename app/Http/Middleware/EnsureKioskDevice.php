<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The kiosk page only opens on a tablet that an admin has switched to kiosk mode.
 * The cookie is encrypted by Laravel, so it cannot be forged by visitors.
 */
class EnsureKioskDevice
{
    public const COOKIE = 'dk_kios';

    public static function isKiosk(Request $request): bool
    {
        return $request->cookie(self::COOKIE) === 'aktif';
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! static::isKiosk($request)) {
            return response()->view('kios-nonaktif', status: 403);
        }

        return $next($request);
    }
}
