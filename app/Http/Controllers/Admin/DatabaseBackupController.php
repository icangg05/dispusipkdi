<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the whole database as an .sql file that can be imported to restore the site,
 * gzip-compressed unless ?format=sql asks for the plain script.
 */
class DatabaseBackupController extends Controller
{
    public function __invoke(Request $request, DatabaseBackup $backup): StreamedResponse
    {
        $compressed = $request->query('format') !== 'sql';

        return response()->streamDownload(function () use ($backup, $compressed): void {
            $out = fopen('php://output', 'w');

            if ($compressed) {
                // Window 31 = zlib's gzip framing, so the stream is a regular .gz file.
                stream_filter_append($out, 'zlib.deflate', STREAM_FILTER_WRITE, ['level' => 6, 'window' => 31]);
            }

            $backup->write($out);
            fclose($out);
        }, $backup->filename($compressed), [
            'Content-Type' => $compressed ? 'application/gzip' : 'application/sql; charset=UTF-8',
        ]);
    }
}
