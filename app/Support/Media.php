<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Media
{
    /** Longest side, in pixels, an uploaded image is scaled down to: full width of the hero on a 1080p screen. */
    public const MAX_SIDE = 1920;

    /** WebP quality (0 to 100). 80 looks like the original on photos at a fraction of a JPEG's size. */
    public const QUALITY = 80;

    /** Images with more pixels than this are stored as uploaded: decoding them would not fit in PHP's memory. */
    public const MAX_PIXELS = 40_000_000;

    /**
     * Public URL for a path on the "public" disk; full URLs and site paths ("/images/…") pass through.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Store an uploaded image on the public disk as a compressed WebP, replacing (and deleting) a previous upload.
     * A file GD cannot read is stored as uploaded.
     */
    public static function store(UploadedFile $file, string $folder, ?string $replace = null, int $maxSide = self::MAX_SIDE): string
    {
        $webp = static::toWebp($file, $maxSide);

        if ($webp === null) {
            $path = $file->store($folder, 'public');
        } else {
            $path = $folder.'/'.Str::random(40).'.webp';
            Storage::disk('public')->put($path, $webp);
        }

        if ($replace && $replace !== $path) {
            static::delete($replace);
        }

        return $path;
    }

    /**
     * Delete an uploaded file. Bundled sample images ("/images/…") and external URLs are left alone.
     */
    public static function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Encode an image as WebP: turned upright from its EXIF orientation, scaled down so its longest side is at most
     * $maxSide, transparency kept. A WebP that needs no scaling keeps its own bytes when re-encoding would not
     * make it smaller. Returns null when the image cannot be converted.
     */
    protected static function toWebp(UploadedFile $file, int $maxSide): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        try {
            $bytes = $file->get();
            $info = getimagesizefromstring($bytes);

            if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) {
                return null;
            }

            $image = imagecreatefromstring($bytes);
            imagepalettetotruecolor($image);

            if ($info[2] === IMAGETYPE_JPEG) {
                $image = static::upright($image, $bytes);
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $scale = $maxSide / max($width, $height);
            $scaled = $scale < 1;

            if ($scaled) {
                $resized = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
                imagealphablending($resized, false);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
                $image = $resized;
            }

            imagesavealpha($image, true);
            ob_start();
            imagewebp($image, null, self::QUALITY);
            $webp = (string) ob_get_clean();
        } catch (Throwable) {
            return null;
        }

        if ($webp === '') {
            return null;
        }

        return $info[2] === IMAGETYPE_WEBP && ! $scaled && strlen($webp) >= strlen($bytes) ? $bytes : $webp;
    }

    /**
     * Rotate a JPEG the way its camera recorded it was held; GD ignores the EXIF orientation tag.
     */
    protected static function upright(GdImage $image, string $bytes): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $bytes);
        rewind($stream);
        $orientation = (int) (@exif_read_data($stream)['Orientation'] ?? 1);
        fclose($stream);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
