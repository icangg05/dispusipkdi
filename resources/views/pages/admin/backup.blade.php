<?php

use App\Services\DatabaseBackup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Backup database')] class extends Component
{
    public function with(): array
    {
        $backup = app(DatabaseBackup::class);
        $tables = $backup->tables();
        $sizes = array_filter(array_column($tables, 'size'), fn (?int $size): bool => $size !== null);

        return [
            'database' => $backup->databaseName(),
            'tables' => $tables,
            'totalRows' => array_sum(array_column($tables, 'rows')),
            'totalSize' => $sizes === [] ? null : array_sum($sizes),
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Backup database</h1>
            <p>Unduh salinan lengkap database situs: berita, galeri, buku tamu, pertanyaan, pengaturan, dan akun admin. Simpan salinan secara berkala, misalnya sebulan sekali.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="secondary" icon="file-text" :href="route('admin.backup.unduh', ['format' => 'sql'])">Tanpa kompresi (.sql)</x-dk.button>
            <x-dk.button variant="primary" icon="download" :href="route('admin.backup.unduh')">Unduh backup (.sql.gz)</x-dk.button>
        </div>
    </div>

    <div class="pnl-grid pnl-grid--main">
        <div class="pnl-card">
            <div class="pnl-card__head">
                <h2 class="pnl-card__title"><x-dk.icon name="database" :size="18" /> Isi database {{ $database }}</h2>
                <span class="pnl-help">{{ collect([count($tables).' tabel', \App\Support\Format::number($totalRows).' baris', $totalSize !== null ? \App\Support\Format::bytes($totalSize) : null])->filter()->join(' · ') }}</span>
            </div>
            <div class="pnl-tablewrap">
                <table class="pnl-table">
                    <thead><tr><th>Tabel</th><th class="num">Baris</th><th class="num">Ukuran</th></tr></thead>
                    <tbody>
                        @foreach ($tables as $table)
                            <tr wire:key="tabel-{{ $table['name'] }}">
                                <td>
                                    <strong>{{ $table['name'] }}</strong>
                                    @unless ($table['with_data'])
                                        <small>Hanya struktur. Isinya data sementara, tidak ikut diunduh.</small>
                                    @endunless
                                </td>
                                <td class="num">{{ \App\Support\Format::number($table['rows']) }}</td>
                                <td class="num whitespace-nowrap">{{ $table['size'] !== null ? \App\Support\Format::bytes($table['size']) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pnl-grid content-start">
            <div class="pnl-card">
                <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="shield-check" :size="18" /> Simpan dengan aman</h2></div>
                <div class="pnl-card__body grid gap-3 text-[14px] leading-[22px]">
                    <p class="m-0">Berkas backup berisi data pribadi tamu (nama, asal, nomor WhatsApp) dan akun admin. Simpan di tempat yang hanya bisa dibuka petugas, jangan dibagikan atau diunggah ke tempat umum.</p>
                    <p class="m-0 text-ink-muted">Gambar yang diunggah (sampul berita, galeri, slider, foto tamu) tersimpan sebagai berkas di folder <code>storage</code>, bukan di database, sehingga tidak ikut dalam backup ini.</p>
                </div>
            </div>

            <div class="pnl-card">
                <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="info" :size="18" /> Cara memulihkan</h2></div>
                <div class="pnl-card__body">
                    <ol class="m-0 grid list-decimal gap-2 pl-5 text-[14px] leading-[22px]">
                        <li>Siapkan database MySQL yang kosong.</li>
                        <li>Impor berkas lewat phpMyAdmin (menu Impor menerima .sql.gz langsung) atau perintah <code>gunzip &lt; berkas.sql.gz | mysql -u pengguna -p nama_database</code></li>
                        <li>Arahkan pengaturan <code>DB_DATABASE</code> di berkas .env ke database tersebut.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
