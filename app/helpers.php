<?php

use App\Support\Icons;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Livewire\Livewire;

if (! function_exists('site')) {
    /**
     * Site-wide settings (identity, contact, social media, service hours, …).
     */
    function site(): SiteSettings
    {
        return app(SiteSettings::class);
    }
}

if (! function_exists('icon')) {
    /**
     * Inline SVG for a Lucide or brand icon.
     */
    function icon(string $name, int|float $size = 20, int|float $strokeWidth = 2, ?string $class = null, ?string $title = null): string
    {
        return Icons::svg($name, $size, $strokeWidth, $class, $title);
    }
}

if (! function_exists('is_internal_page')) {
    /**
     * Whether a link opens another page of this site, so it can use wire:navigate.
     * In-page anchors, mail/phone links, files and other sites keep normal browser behaviour.
     */
    function is_internal_page(?string $href): bool
    {
        if (blank($href) || str_starts_with($href, '#') || str_starts_with($href, '//')) {
            return false;
        }

        $parts = parse_url($href);

        if ($parts === false || pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION) !== '') {
            return false;
        }

        if (! isset($parts['scheme']) && ! isset($parts['host'])) {
            return str_starts_with($href, '/');
        }

        return in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && ($parts['host'] ?? null) === request()->getHost();
    }
}

if (! function_exists('page_route_name')) {
    /**
     * Name of the route of the page being shown. During a Livewire update (a search, a filter, a form) the request
     * goes to Livewire's own endpoint, so the page's route is looked up from the URL the component was loaded on.
     */
    function page_route_name(): ?string
    {
        if (! Livewire::isLivewireRequest()) {
            return request()->route()?->getName();
        }

        return rescue(
            fn (): ?string => app('router')->getRoutes()->match(Request::create(Livewire::originalUrl()))->getName(),
            null,
            false,
        );
    }
}
