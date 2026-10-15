<?php

namespace App\Support;

/**
 * Renders Lucide stroke icons and social brand glyphs as inline SVG.
 */
class Icons
{
    /** @var array{stroke: array<string, string>, brand: array<string, string>}|null */
    protected static ?array $set = null;

    /**
     * @return array{stroke: array<string, string>, brand: array<string, string>}
     */
    public static function set(): array
    {
        return static::$set ??= require resource_path('icons/icons.php');
    }

    public static function has(string $name): bool
    {
        $set = static::set();

        return isset($set['stroke'][$name]) || isset($set['brand'][$name]);
    }

    public static function svg(string $name, int|float $size = 20, int|float $strokeWidth = 2, ?string $class = null, ?string $title = null): string
    {
        $set = static::set();
        $cls = trim('dk-icon '.$class);
        $a11y = $title
            ? 'role="img" aria-label="'.e($title).'"'
            : 'aria-hidden="true" focusable="false"';

        if (isset($set['brand'][$name])) {
            return '<svg class="'.e($cls).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" '.$a11y.'><path d="'.$set['brand'][$name].'"/></svg>';
        }

        return '<svg class="'.e($cls).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$strokeWidth.'" stroke-linecap="round" stroke-linejoin="round" '.$a11y.'>'.($set['stroke'][$name] ?? '').'</svg>';
    }
}
