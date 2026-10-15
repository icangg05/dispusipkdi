<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Services\GuestbookService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a guest's photo from private storage to signed-in admins only.
 */
class GuestPhotoController extends Controller
{
    public function __invoke(Guest $guest): StreamedResponse
    {
        $disk = Storage::disk(GuestbookService::PHOTO_DISK);

        abort_unless($guest->foto && $disk->exists($guest->foto), 404);

        return $disk->response($guest->foto, headers: [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
