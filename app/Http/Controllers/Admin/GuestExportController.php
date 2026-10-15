<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the guest register for a date range as CSV (opens in Excel / LibreOffice).
 */
class GuestExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'kategori' => ['nullable', 'string', 'max:40'],
        ]);

        $from = Carbon::parse($validated['dari'] ?? today()->startOfMonth());
        $to = Carbon::parse($validated['sampai'] ?? today());

        $query = Guest::query()
            ->whereBetween('visit_date', [$from->toDateString(), $to->toDateString()])
            ->when($validated['kategori'] ?? null, fn ($q, string $k) => $q->where('kategori', $k))
            ->orderBy('visit_date')
            ->orderBy('nomor');

        $filename = 'buku-tamu-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'No.', 'Jam', 'Nama', 'Asal', 'Datang sebagai', 'Keperluan', 'Jenis kelamin', 'Jumlah orang', 'WhatsApp', 'Sumber'], ';');

            $query->chunk(500, function ($guests) use ($out): void {
                foreach ($guests as $guest) {
                    fputcsv($out, [
                        $guest->visit_date->format('Y-m-d'),
                        $guest->nomorLabel(),
                        $guest->created_at?->format('H.i'),
                        $guest->nama,
                        $guest->asal,
                        $guest->kategori,
                        $guest->keperluan,
                        $guest->jenis_kelamin,
                        $guest->jumlah,
                        $guest->whatsapp,
                        $guest->sumber,
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
