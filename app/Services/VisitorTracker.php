<?php

namespace App\Services;

use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Counts unique website visitors per day. A visitor is an anonymous hash of IP address,
 * browser and date; no personal data is stored. A cookie avoids repeat writes the same day.
 */
class VisitorTracker
{
    public const COOKIE = 'dk_kunjungan';

    public function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->hasHeader('X-Livewire') || $request->prefetch()) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'livewire*', 'storage/*', 'build/*', 'up', 'kios')) {
            return false;
        }

        $agent = (string) $request->userAgent();

        if ($agent === '' || preg_match(config('dispusip.bot_pattern'), $agent)) {
            return false;
        }

        return $request->cookie(self::COOKIE) !== today()->toDateString();
    }

    /**
     * Record the visit and remember it in a cookie until midnight WITA.
     */
    public function track(Request $request): void
    {
        $date = today()->toDateString();
        $hash = hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.$date, (string) config('app.key'));

        SiteVisit::query()->upsert(
            [[
                'visit_date' => $date,
                'visitor_hash' => $hash,
                'hits' => 1,
                'landing_path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['visit_date', 'visitor_hash'],
            ['hits' => DB::raw('hits + 1'), 'updated_at' => now()],
        );

        $minutesLeft = max(1, (int) ceil(now()->diffInMinutes(today()->endOfDay())));

        Cookie::queue(self::COOKIE, $date, $minutesLeft, httpOnly: true, sameSite: 'lax');
    }
}
