<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\GuestbookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shows a guest the photo they just took, pinned to their stamped sheet — only in the same browser session.
 */
class OwnGuestPhotoController extends Controller
{
    public function __invoke(Request $request, Guest $guest): StreamedResponse
    {
        abort_unless((int) $request->session()->get('tamu_foto') === $guest->id, 404);

        $disk = Storage::disk(GuestbookService::PHOTO_DISK);

        abort_unless($guest->foto && $disk->exists($guest->foto), 404);

        return $disk->response($guest->foto, headers: ['Cache-Control' => 'private, no-store']);
    }
}
