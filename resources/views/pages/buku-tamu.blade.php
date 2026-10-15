<?php

use App\Services\GuestbookService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Buku Tamu')] class extends Component
{
    public ?int $baru = null;

    #[On('tamu-tercatat')]
    public function tamuTercatat(int $id): void
    {
        $this->baru = $id;
    }

    public function with(): array
    {
        $guestbook = app(GuestbookService::class);

        return [
            'entries' => $guestbook->today(12),
            'total' => $guestbook->nextNumber() - 1,
        ];
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Buku Tamu']]" title="Buku Tamu"
        text="Setiap pengunjung perpustakaan mengisi satu lembar. Isi dari ponsel Anda atau tablet di meja layanan; cukup kurang dari satu menit.">
        <x-slot:aside>
            <div class="dk-hariini">
                <span class="dk-hariini__date">{{ \App\Support\Format::longDate() }}</span>
                <x-dk.status variant="dot" />
            </div>
        </x-slot:aside>
    </x-dk.page-top>

    <section class="dk-section dk-section--tight" id="buku-tamu">
        <div class="dk-container">
            <div class="dk-gbsec">
                <livewire:guestbook />
                <x-dk.visitor-log :entries="$entries" :total="$total" :max="12" :highlight="$baru" />
            </div>
        </div>
    </section>

    <section class="dk-section dk-section--sunken dk-section--tight">
        <div class="dk-container dk-btinfo">
            <x-dk.service-hours />
            <div class="dk-note">
                <h2 class="dk-note__title">Di mana kode kunjungan?</h2>
                <p>Kode empat angka tertera di meja layanan dan berganti setiap hari. Kode ini memastikan buku tamu hanya diisi oleh pengunjung yang benar-benar datang.</p>
            </div>
            <div class="dk-note">
                <h2 class="dk-note__title">Datang berombongan?</h2>
                <p>Sekolah, kampus, atau komunitas yang datang lebih dari 10 orang mohon menghubungi kami paling lambat 3 hari sebelumnya, agar ruang baca dan pemandu bisa disiapkan.</p>
                @if (site()->kontak()['whatsappLink'])
                    <x-dk.button variant="secondary" icon="whatsapp" :href="site()->kontak()['whatsappLink']" target="_blank" rel="noopener">Atur jadwal lewat WhatsApp</x-dk.button>
                @endif
            </div>
        </div>
    </section>

    <section class="dk-section dk-section--tight">
        <div class="dk-container">
            <div class="dk-note">
                <h2 class="dk-note__title">Untuk apa data ini?</h2>
                <p>Jumlah dan keperluan tamu dipakai untuk menentukan jam layanan, menambah koleksi yang paling dicari, dan menyusun laporan statistik bulanan. Nama tamu tidak pernah ditampilkan utuh, dan foto hanya dapat dilihat petugas.</p>
            </div>
        </div>
    </section>
</div>
