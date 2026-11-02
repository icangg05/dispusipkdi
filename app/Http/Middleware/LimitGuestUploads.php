<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire's temporary upload endpoint accepts files up to the admin upload ceiling (150 MB).
 * Visitors only ever send a guestbook photo, so their uploads stay at Livewire's old 12 MB cap
 * and cannot fill the server's disk with large temporary files.
 */
class LimitGuestUploads
{
    public const GUEST_MAX_KB = 12 * 1024;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() && (int) $request->server('CONTENT_LENGTH') > self::GUEST_MAX_KB * 1024) {
            abort(413);
        }

        return $next($request);
    }
}
