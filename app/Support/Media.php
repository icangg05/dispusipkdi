<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media
{
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
     * Store an uploaded image on the public disk, replacing (and deleting) a previous upload.
     */
    public static function store(UploadedFile $file, string $folder, ?string $replace = null): string
    {
        $path = $file->store($folder, 'public');

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
}
