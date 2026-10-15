<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureKioskDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * Switches the current device (the front-desk tablet) in or out of kiosk mode.
 * In kiosk mode the guestbook does not ask for the visit code.
 */
class KioskController extends Controller
{
    /**
     * The admin is signed out on this device so visitors at the tablet cannot reach the admin panel.
     */
    public function activate(Request $request): RedirectResponse
    {
        $minutes = (int) config('dispusip.kios_cookie_days') * 24 * 60;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('kios')
            ->withCookie(Cookie::make(EnsureKioskDevice::COOKIE, 'aktif', $minutes, httpOnly: true, sameSite: 'lax'));
    }

    public function deactivate(): RedirectResponse
    {
        return redirect()->route('admin.kode')
            ->withCookie(Cookie::forget(EnsureKioskDevice::COOKIE))
            ->with('status', 'Mode kios di perangkat ini dimatikan.');
    }
}
