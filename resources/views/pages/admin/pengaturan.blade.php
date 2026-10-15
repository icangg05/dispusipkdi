<?php

use App\Support\Format;
use App\Support\Jadwal;
use App\Support\Media;
use App\Support\SiteSettings;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] #[Title('Pengaturan')] class extends Component
{
    use WithFileUploads;

    #[Url(except: 'identitas')]
    public string $tab = 'identitas';

    public array $identitas = [];

    public array $profil = [];

    public array $kontak = [];

    public array $sosmed = [];

    /** @var array<int, array{buka: bool, sesi: array<int, array{mulai: string, selesai: string}>}> */
    public array $jadwal = [];

    public array $angka = [];

    public bool $fotoWajib = true;

    public $logo = null;

    public $gambarUtama = null;

    public $gambarKedua = null;

    public $struktur = null;

    public function mount(SiteSettings $settings): void
    {
        $this->identitas = collect($settings->get('identitas'))->only(['nama', 'singkatan', 'kota', 'deskripsi', 'logo'])->all();
        $profil = $settings->profil();
        $this->profil = [
            'ringkas' => $profil['ringkas'] ?? '',
            'sejarah' => $profil['sejarah'] ?? '',
            'visi' => $profil['visi'] ?? '',
            'misi' => array_values($profil['misi'] ?? []),
            'tugas' => array_values($profil['tugas'] ?? []),
            'gambar_utama' => $profil['gambar_utama'] ?? null,
            'gambar_kedua' => $profil['gambar_kedua'] ?? null,
            'struktur' => $profil['struktur'] ?? null,
        ];
        $this->kontak = collect($settings->get('kontak'))->only(['whatsapp', 'email', 'telepon', 'alamat', 'catatan_alamat', 'maps_link', 'maps_embed'])->map(fn ($v) => (string) $v)->all();

        foreach (array_keys(SiteSettings::PLATFORMS) as $platform) {
            $row = $settings->get('sosmed.'.$platform, []);
            $this->sosmed[$platform] = ['handle' => (string) ($row['handle'] ?? ''), 'url' => (string) ($row['url'] ?? ''), 'followers' => (string) ($row['followers'] ?? '')];
        }

        foreach ($settings->jadwal() as $day => $entry) {
            $sesi = array_map(fn (array $s): array => ['mulai' => $this->time($s[0]), 'selesai' => $this->time($s[1])], $entry['sesi']);
            $this->jadwal[$day] = ['buka' => $sesi !== [], 'sesi' => $sesi ?: [['mulai' => '08:00', 'selesai' => '15:30']]];
        }

        $angka = $settings->get('angka', []);

        for ($i = 0; $i < 4; $i++) {
            $this->angka[$i] = ['icon' => $angka[$i]['icon'] ?? 'book-open', 'value' => (string) ($angka[$i]['value'] ?? ''), 'label' => (string) ($angka[$i]['label'] ?? '')];
        }

        $this->fotoWajib = $settings->fotoTamuWajib();
    }

    protected function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    protected function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time) + [0, 0]);

        return $h * 60 + $m;
    }

    public function tambahMisi(): void
    {
        $this->profil['misi'][] = '';
    }

    public function hapusMisi(int $i): void
    {
        unset($this->profil['misi'][$i]);
        $this->profil['misi'] = array_values($this->profil['misi']);
    }

    public function tambahTugas(): void
    {
        $this->profil['tugas'][] = ['icon' => 'book-open', 'title' => '', 'text' => ''];
    }

    public function hapusTugas(int $i): void
    {
        unset($this->profil['tugas'][$i]);
        $this->profil['tugas'] = array_values($this->profil['tugas']);
    }

    public function tambahSesi(int $day): void
    {
        if (count($this->jadwal[$day]['sesi']) < 2) {
            $this->jadwal[$day]['sesi'][] = ['mulai' => '13:30', 'selesai' => '16:00'];
        }
    }

    public function hapusSesi(int $day, int $i): void
    {
        unset($this->jadwal[$day]['sesi'][$i]);
        $this->jadwal[$day]['sesi'] = array_values($this->jadwal[$day]['sesi']) ?: [['mulai' => '08:00', 'selesai' => '15:30']];
    }

    public function hapusGambar(string $key): void
    {
        if ($key === 'logo') {
            Media::delete($this->identitas['logo'] ?? null);
            $this->identitas['logo'] = null;
            site()->put('identitas', $this->identitas);
        } elseif (in_array($key, ['gambar_utama', 'gambar_kedua', 'struktur'], true)) {
            Media::delete($this->profil[$key] ?? null);
            $this->profil[$key] = null;
            site()->put('profil', $this->profil);
        }

        $this->dispatch('toast', message: 'Gambar dihapus.');
    }

    public function simpanIdentitas(SiteSettings $settings): void
    {
        $this->profil['misi'] = array_values(array_filter(array_map('trim', $this->profil['misi']), 'strlen'));

        $this->validate([
            'identitas.nama' => ['required', 'string', 'max:120'],
            'identitas.singkatan' => ['required', 'string', 'max:20'],
            'identitas.kota' => ['required', 'string', 'max:40'],
            'identitas.deskripsi' => ['required', 'string', 'max:300'],
            'profil.ringkas' => ['required', 'string', 'max:600'],
            'profil.sejarah' => ['nullable', 'string', 'max:5000'],
            'profil.visi' => ['required', 'string', 'max:300'],
            'profil.misi' => ['array', 'min:1'],
            'profil.misi.*' => ['string', 'max:300'],
            'profil.tugas' => ['array', 'min:1'],
            'profil.tugas.*.icon' => ['required', Rule::in(config('dispusip.ikon_pilihan'))],
            'profil.tugas.*.title' => ['required', 'string', 'max:60'],
            'profil.tugas.*.text' => ['required', 'string', 'max:160'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'gambarUtama' => ['nullable', 'image', 'max:4096'],
            'gambarKedua' => ['nullable', 'image', 'max:4096'],
            'struktur' => ['nullable', 'image', 'max:6144'],
        ], [
            'required' => 'Kolom ini wajib diisi.',
            'profil.misi.min' => 'Tulis minimal satu misi.',
            'profil.tugas.min' => 'Tulis minimal satu tugas.',
            'image' => 'Berkas harus berupa gambar.',
            'max' => 'Terlalu panjang atau terlalu besar.',
        ]);

        if ($this->logo) {
            $this->identitas['logo'] = Media::store($this->logo, 'situs', $this->identitas['logo'] ?? null);
        }

        foreach (['gambarUtama' => 'gambar_utama', 'gambarKedua' => 'gambar_kedua', 'struktur' => 'struktur'] as $prop => $key) {
            if ($this->{$prop}) {
                $this->profil[$key] = Media::store($this->{$prop}, 'situs', $this->profil[$key] ?? null);
            }
        }

        $settings->put('identitas', $this->identitas);
        $settings->put('profil', $this->profil);
        $this->reset('logo', 'gambarUtama', 'gambarKedua', 'struktur');
        $this->dispatch('toast', message: 'Identitas dan profil dinas disimpan.');
    }

    public function simpanKontak(SiteSettings $settings): void
    {
        $embed = trim($this->kontak['maps_embed'] ?? '');

        if (str_contains($embed, '<iframe') && preg_match('/src=["\']([^"\']+)["\']/i', $embed, $m)) {
            $embed = html_entity_decode($m[1]);
        }

        $this->kontak['maps_embed'] = $embed;

        $this->validate([
            'kontak.whatsapp' => ['nullable', 'regex:/^[0-9+\-\s().]{8,20}$/'],
            'kontak.email' => ['nullable', 'email', 'max:120'],
            'kontak.telepon' => ['nullable', 'string', 'max:30'],
            'kontak.alamat' => ['required', 'string', 'max:255'],
            'kontak.catatan_alamat' => ['nullable', 'string', 'max:120'],
            'kontak.maps_link' => ['nullable', 'url', 'max:500'],
            'kontak.maps_embed' => ['nullable', 'url', 'max:2000', 'regex:~^https://(www\.google\.com/maps/embed|maps\.google\.com/maps|www\.openstreetmap\.org/export/embed)~'],
        ], [
            'kontak.whatsapp.regex' => 'Periksa lagi nomor WhatsApp ini.',
            'kontak.email.email' => 'Periksa lagi alamat email ini.',
            'kontak.alamat.required' => 'Tulis alamat kantor.',
            'kontak.maps_link.url' => 'Tautan peta harus diawali https://.',
            'kontak.maps_embed.*' => 'Tempel kode sematan dari Google Maps (Bagikan › Sematkan peta) atau OpenStreetMap.',
        ]);

        $settings->put('kontak', array_map(fn ($v) => $v === '' ? null : $v, $this->kontak));
        $this->dispatch('toast', message: 'Kontak disimpan.');
    }

    public function simpanSosmed(SiteSettings $settings): void
    {
        $this->validate([
            'sosmed.*.url' => ['nullable', 'url', 'max:255'],
            'sosmed.*.handle' => ['nullable', 'string', 'max:60'],
            'sosmed.*.followers' => ['nullable', 'string', 'max:40'],
        ], [
            'sosmed.*.url.url' => 'Tautan harus diawali https://.',
        ]);

        $settings->put('sosmed', $this->sosmed);
        $this->dispatch('toast', message: 'Media sosial disimpan. Akun tanpa tautan tidak ditampilkan.');
    }

    public function simpanJadwal(SiteSettings $settings): void
    {
        $this->validate([
            'jadwal.*.sesi.*.mulai' => ['required', 'date_format:H:i'],
            'jadwal.*.sesi.*.selesai' => ['required', 'date_format:H:i'],
        ], [
            'required' => 'Isi jam.',
            'date_format' => 'Format jam 08:00.',
        ]);

        $value = [];

        foreach (Jadwal::HARI as $day => $hari) {
            $sesi = [];

            if ($this->jadwal[$day]['buka']) {
                foreach ($this->jadwal[$day]['sesi'] as $i => $s) {
                    $start = $this->minutes($s['mulai']);
                    $end = $this->minutes($s['selesai']);

                    if ($end <= $start) {
                        $this->addError("jadwal.$day.sesi.$i.selesai", 'Jam tutup harus setelah jam buka.');

                        return;
                    }

                    $sesi[] = [$start, $end];
                }

                usort($sesi, fn (array $a, array $b): int => $a[0] <=> $b[0]);
            }

            $value[$day] = ['hari' => $hari, 'sesi' => $sesi];
        }

        $settings->put('jadwal', $value);
        $this->dispatch('toast', message: 'Jam layanan disimpan.');
    }

    public function simpanAngka(SiteSettings $settings): void
    {
        $this->validate([
            'angka.*.icon' => ['required', Rule::in(config('dispusip.ikon_pilihan'))],
            'angka.*.value' => ['nullable', 'string', 'max:20'],
            'angka.*.label' => ['required_with:angka.*.value', 'nullable', 'string', 'max:40'],
        ], [
            'angka.*.label.required_with' => 'Tulis keterangan angka ini.',
        ]);

        $settings->put('angka', $this->angka);
        $this->dispatch('toast', message: 'Angka utama disimpan.');
    }

    public function simpanBukuTamu(SiteSettings $settings): void
    {
        $settings->put('bukutamu', ['foto_wajib' => $this->fotoWajib]);
        $this->dispatch('toast', message: 'Pengaturan buku tamu disimpan.');
    }

    public function with(): array
    {
        return ['hariUrut' => Jadwal::URUTAN, 'namaHari' => Jadwal::HARI];
    }
};
?>

@php
    $tabs = [
        'identitas' => ['Identitas & profil', 'landmark'],
        'kontak' => ['Kontak & peta', 'map-pin'],
        'sosmed' => ['Media sosial', 'globe'],
        'jadwal' => ['Jam layanan', 'clock'],
        'angka' => ['Angka utama', 'chart-column'],
        'bukutamu' => ['Buku tamu', 'notebook-pen'],
    ];
@endphp
<div class="ad-grid" style="gap: var(--space-6)">
    <div class="ad-pagehead">
        <div>
            <h1>Pengaturan situs</h1>
            <p>Informasi dinas yang tampil di seluruh situs. Semua isi bawaan adalah contoh. Ganti dengan data resmi sebelum situs diluncurkan.</p>
        </div>
    </div>

    <div class="dk-chips" role="tablist" aria-label="Bagian pengaturan">
        @foreach ($tabs as $key => [$label, $icon])
            <button type="button" role="tab" @class(['dk-chip', 'is-active' => $tab === $key]) aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')">
                <x-dk.icon :name="$icon" :size="15" /> {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Identitas & profil --}}
    @if ($tab === 'identitas')
        <form class="ad-grid" style="gap: var(--space-6)" wire:submit="simpanIdentitas">
            <div class="ad-card">
                <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="building-2" :size="18" /> Identitas</h2></div>
                <div class="ad-card__body ad-form">
                    <div class="ad-grid ad-grid--3">
                        <x-ad.field label="Nama dinas" for="i-nama" error="identitas.nama" class="md:col-span-2"><input id="i-nama" class="ad-input" wire:model="identitas.nama"></x-ad.field>
                        <x-ad.field label="Singkatan" for="i-singkatan" error="identitas.singkatan"><input id="i-singkatan" class="ad-input" wire:model="identitas.singkatan" maxlength="20"></x-ad.field>
                    </div>
                    <div class="ad-grid ad-grid--3">
                        <x-ad.field label="Kota" for="i-kota" error="identitas.kota"><input id="i-kota" class="ad-input" wire:model="identitas.kota" maxlength="40"></x-ad.field>
                        <x-ad.field label="Deskripsi singkat (footer)" for="i-desk" error="identitas.deskripsi" class="md:col-span-2"><textarea id="i-desk" class="ad-textarea" rows="2" wire:model="identitas.deskripsi" maxlength="300"></textarea></x-ad.field>
                    </div>
                    <x-ad.field label="Logo" error="logo" optional help="Logo resmi Pemkot Kendari / Dispusip, persegi dengan latar transparan (PNG). Tanpa logo, situs memakai ikon perpustakaan.">
                        <x-ad.image-upload model="logo" :file="$logo" :current="\App\Support\Media::url($identitas['logo'] ?? null)" square>
                            @if (! empty($identitas['logo']))
                                <x-dk.button variant="ghost" size="sm" icon="trash-2" wire:click="hapusGambar('logo')" wire:confirm="Hapus logo?">Hapus logo</x-dk.button>
                            @endif
                        </x-ad.image-upload>
                    </x-ad.field>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="landmark" :size="18" /> Profil dinas</h2></div>
                <div class="ad-card__body ad-form">
                    <x-ad.field label="Profil ringkas" for="p-ringkas" error="profil.ringkas" help="2 sampai 3 kalimat; tampil di Beranda dan halaman Profil."><textarea id="p-ringkas" class="ad-textarea" rows="3" wire:model="profil.ringkas"></textarea></x-ad.field>
                    <x-ad.field label="Sejarah singkat" for="p-sejarah" error="profil.sejarah" optional help="Pisahkan paragraf dengan baris kosong."><textarea id="p-sejarah" class="ad-textarea" rows="5" wire:model="profil.sejarah"></textarea></x-ad.field>
                    <x-ad.field label="Visi" for="p-visi" error="profil.visi"><textarea id="p-visi" class="ad-textarea" rows="2" wire:model="profil.visi"></textarea></x-ad.field>
                    <fieldset class="ad-fieldset">
                        <legend class="ad-legend">Misi</legend>
                        <div class="ad-repeat">
                            @foreach ($profil['misi'] as $i => $misi)
                                <div class="ad-repeat__row" wire:key="misi-{{ $i }}">
                                    <x-ad.field error="profil.misi.{{ $i }}"><input class="ad-input" wire:model="profil.misi.{{ $i }}" aria-label="Misi {{ $i + 1 }}"></x-ad.field>
                                    <button type="button" class="dk-iconbtn ad-iconbtn--danger" wire:click="hapusMisi({{ $i }})" aria-label="Hapus misi {{ $i + 1 }}"><x-dk.icon name="trash-2" :size="17" /></button>
                                </div>
                            @endforeach
                        </div>
                        @error('profil.misi')<span class="ad-error">{{ $message }}</span>@enderror
                        <div><x-dk.button variant="ghost" size="sm" icon="plus" wire:click="tambahMisi">Tambah misi</x-dk.button></div>
                    </fieldset>
                    <fieldset class="ad-fieldset">
                        <legend class="ad-legend">Tugas &amp; fungsi</legend>
                        @foreach ($profil['tugas'] as $i => $tugas)
                            <div class="grid gap-3 rounded-md border border-line p-4" wire:key="tugas-{{ $i }}">
                                <div class="ad-grid ad-grid--2">
                                    <x-ad.field label="Judul" error="profil.tugas.{{ $i }}.title"><input class="ad-input" wire:model="profil.tugas.{{ $i }}.title" maxlength="60"></x-ad.field>
                                    <x-ad.field label="Keterangan" error="profil.tugas.{{ $i }}.text"><input class="ad-input" wire:model="profil.tugas.{{ $i }}.text" maxlength="160"></x-ad.field>
                                </div>
                                <x-ad.field label="Ikon" error="profil.tugas.{{ $i }}.icon"><x-ad.icon-select model="profil.tugas.{{ $i }}.icon" /></x-ad.field>
                                <div><x-dk.button variant="ghost" size="sm" icon="trash-2" wire:click="hapusTugas({{ $i }})">Hapus tugas ini</x-dk.button></div>
                            </div>
                        @endforeach
                        @error('profil.tugas')<span class="ad-error">{{ $message }}</span>@enderror
                        <div><x-dk.button variant="ghost" size="sm" icon="plus" wire:click="tambahTugas">Tambah tugas</x-dk.button></div>
                    </fieldset>
                    <fieldset class="ad-fieldset">
                        <legend class="ad-legend">Gambar</legend>
                        <div class="ad-grid ad-grid--2">
                            <x-ad.field label="Foto besar (belakang)" error="gambarUtama" optional>
                                <x-ad.image-upload model="gambarUtama" :file="$gambarUtama" :current="\App\Support\Media::url($profil['gambar_utama'] ?? null)" />
                            </x-ad.field>
                            <x-ad.field label="Foto kecil (depan)" error="gambarKedua" optional>
                                <x-ad.image-upload model="gambarKedua" :file="$gambarKedua" :current="\App\Support\Media::url($profil['gambar_kedua'] ?? null)" />
                            </x-ad.field>
                        </div>
                        <x-ad.field label="Bagan struktur organisasi" error="struktur" optional help="Tampil di halaman Profil.">
                            <x-ad.image-upload model="struktur" :file="$struktur" :current="\App\Support\Media::url($profil['struktur'] ?? null)">
                                @if (! empty($profil['struktur']))
                                    <x-dk.button variant="ghost" size="sm" icon="trash-2" wire:click="hapusGambar('struktur')" wire:confirm="Hapus bagan struktur?">Hapus bagan</x-dk.button>
                                @endif
                            </x-ad.image-upload>
                        </x-ad.field>
                    </fieldset>
                </div>
                <div class="ad-card__foot"><span class="ad-help">Perubahan langsung tampil di situs setelah disimpan.</span><x-dk.button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="simpanIdentitas, logo, gambarUtama, gambarKedua, struktur">Simpan identitas &amp; profil</x-dk.button></div>
            </div>
        </form>
    @endif

    {{-- Kontak --}}
    @if ($tab === 'kontak')
        <form class="ad-card" wire:submit="simpanKontak">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="phone" :size="18" /> Kontak &amp; lokasi</h2></div>
            <div class="ad-card__body ad-form">
                <div class="ad-grid ad-grid--3">
                    <x-ad.field label="Nomor WhatsApp" for="k-wa" error="kontak.whatsapp" help="Dipakai tombol WhatsApp melayang dan kartu kontak."><input id="k-wa" class="ad-input" wire:model="kontak.whatsapp" inputmode="tel" placeholder="0812-0000-0000"></x-ad.field>
                    <x-ad.field label="Email" for="k-email" error="kontak.email"><input id="k-email" type="email" class="ad-input" wire:model="kontak.email"></x-ad.field>
                    <x-ad.field label="Telepon kantor" for="k-tel" error="kontak.telepon" optional><input id="k-tel" class="ad-input" wire:model="kontak.telepon"></x-ad.field>
                </div>
                <div class="ad-grid ad-grid--main">
                    <x-ad.field label="Alamat kantor" for="k-alamat" error="kontak.alamat"><textarea id="k-alamat" class="ad-textarea" rows="2" wire:model="kontak.alamat"></textarea></x-ad.field>
                    <x-ad.field label="Catatan alamat" for="k-catatan" error="kontak.catatan_alamat" optional help="Misalnya: Ruang baca di lantai 1."><input id="k-catatan" class="ad-input" wire:model="kontak.catatan_alamat"></x-ad.field>
                </div>
                <x-ad.field label="Tautan petunjuk arah" for="k-link" error="kontak.maps_link" optional help="Tautan Google Maps lokasi kantor (tombol Petunjuk arah)."><input id="k-link" type="url" class="ad-input" wire:model="kontak.maps_link" placeholder="https://maps.app.goo.gl/…"></x-ad.field>
                <x-ad.field label="Sematan peta" for="k-embed" error="kontak.maps_embed" optional help="Di Google Maps: Bagikan › Sematkan peta › Salin HTML, lalu tempel di sini. Bila kosong, ditampilkan ilustrasi peta.">
                    <textarea id="k-embed" class="ad-textarea font-mono text-[13px]" rows="3" wire:model="kontak.maps_embed" placeholder='<iframe src="https://www.google.com/maps/embed?pb=…"></iframe>'></textarea>
                </x-ad.field>
            </div>
            <div class="ad-card__foot"><span class="ad-help">Jam layanan diatur di tab Jam layanan.</span><x-dk.button type="submit" variant="primary" icon="save">Simpan kontak</x-dk.button></div>
        </form>
    @endif

    {{-- Media sosial --}}
    @if ($tab === 'sosmed')
        <form class="ad-card" wire:submit="simpanSosmed">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="globe" :size="18" /> Media sosial</h2></div>
            <div class="ad-card__body ad-form">
                @foreach (\App\Support\SiteSettings::PLATFORMS as $platform => $label)
                    <div class="grid items-start gap-4 md:grid-cols-[180px_minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.8fr)]" wire:key="sosmed-{{ $platform }}">
                        <span class="flex items-center gap-3 pt-7 font-bold"><span class="dk-soc-card__icon"><x-dk.icon :name="$platform" :size="22" /></span>{{ $label }}</span>
                        <x-ad.field label="Tautan profil" error="sosmed.{{ $platform }}.url"><input type="url" class="ad-input" wire:model="sosmed.{{ $platform }}.url" placeholder="https://"></x-ad.field>
                        <x-ad.field label="Nama akun" error="sosmed.{{ $platform }}.handle"><input class="ad-input" wire:model="sosmed.{{ $platform }}.handle" placeholder="@dispusip.kendari"></x-ad.field>
                        <x-ad.field label="Pengikut" error="sosmed.{{ $platform }}.followers" optional><input class="ad-input" wire:model="sosmed.{{ $platform }}.followers" placeholder="12,4 rb pengikut"></x-ad.field>
                    </div>
                @endforeach
            </div>
            <div class="ad-card__foot"><span class="ad-help">Kosongkan tautan untuk menyembunyikan akun.</span><x-dk.button type="submit" variant="primary" icon="save">Simpan media sosial</x-dk.button></div>
        </form>
    @endif

    {{-- Jam layanan --}}
    @if ($tab === 'jadwal')
        <form class="ad-card" wire:submit="simpanJadwal">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="clock" :size="18" /> Jam layanan (WITA)</h2></div>
            <div class="ad-tablewrap">
                <table class="ad-table">
                    <thead><tr><th>Hari</th><th>Buka</th><th>Sesi</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($hariUrut as $day)
                            <tr wire:key="hari-{{ $day }}">
                                <td class="font-bold">{{ $namaHari[$day] }}</td>
                                <td><x-ad.check model="jadwal.{{ $day }}.buka" live><span class="dk-sr">{{ $namaHari[$day] }} </span>Buka</x-ad.check></td>
                                <td>
                                    @if ($jadwal[$day]['buka'])
                                        <div class="grid gap-2">
                                            @foreach ($jadwal[$day]['sesi'] as $i => $sesi)
                                                <div class="flex flex-wrap items-center gap-2" wire:key="sesi-{{ $day }}-{{ $i }}">
                                                    <input type="time" class="ad-input ad-input--sm !w-auto" wire:model="jadwal.{{ $day }}.sesi.{{ $i }}.mulai" aria-label="{{ $namaHari[$day] }} sesi {{ $i + 1 }} buka">
                                                    <span class="text-ink-muted">sampai</span>
                                                    <input type="time" class="ad-input ad-input--sm !w-auto" wire:model="jadwal.{{ $day }}.sesi.{{ $i }}.selesai" aria-label="{{ $namaHari[$day] }} sesi {{ $i + 1 }} tutup">
                                                    @if ($i > 0)
                                                        <button type="button" class="dk-iconbtn dk-iconbtn--sm" wire:click="hapusSesi({{ $day }}, {{ $i }})" aria-label="Hapus sesi"><x-dk.icon name="x" :size="16" /></button>
                                                    @endif
                                                    @error("jadwal.$day.sesi.$i.selesai")<span class="ad-error">{{ $message }}</span>@enderror
                                                    @error("jadwal.$day.sesi.$i.mulai")<span class="ad-error">{{ $message }}</span>@enderror
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-ink-muted">Tutup</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($jadwal[$day]['buka'] && count($jadwal[$day]['sesi']) < 2)
                                        <x-dk.button variant="ghost" size="sm" icon="plus" wire:click="tambahSesi({{ $day }})">Sesi istirahat</x-dk.button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ad-card__foot"><span class="ad-help">Status <em>Buka sekarang / Tutup</em> di situs dihitung dari jadwal ini. Hari Jumat biasanya dua sesi (istirahat salat Jumat).</span><x-dk.button type="submit" variant="primary" icon="save">Simpan jam layanan</x-dk.button></div>
        </form>
    @endif

    {{-- Angka utama --}}
    @if ($tab === 'angka')
        <form class="ad-card" wire:submit="simpanAngka">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="chart-column" :size="18" /> Angka utama di bawah hero</h2></div>
            <div class="ad-card__body ad-form">
                <p class="ad-help m-0">Empat angka yang selalu diperbarui, misalnya jumlah koleksi, arsip, anggota, dan titik layanan keliling. Bila sebuah angka tidak dipelihara, kosongkan agar tidak tampil angka basi.</p>
                @foreach ($angka as $i => $item)
                    <div class="grid gap-3 rounded-md border border-line p-4" wire:key="angka-{{ $i }}">
                        <div class="ad-grid ad-grid--2">
                            <x-ad.field label="Angka" error="angka.{{ $i }}.value" optional><input class="ad-input" wire:model="angka.{{ $i }}.value" placeholder="32.500+"></x-ad.field>
                            <x-ad.field label="Keterangan" error="angka.{{ $i }}.label"><input class="ad-input" wire:model="angka.{{ $i }}.label" placeholder="Eksemplar koleksi"></x-ad.field>
                        </div>
                        <x-ad.field label="Ikon" error="angka.{{ $i }}.icon"><x-ad.icon-select model="angka.{{ $i }}.icon" /></x-ad.field>
                    </div>
                @endforeach
            </div>
            <div class="ad-card__foot"><span class="ad-help">Tulis angka dengan titik pemisah ribuan, misalnya 14.200.</span><x-dk.button type="submit" variant="primary" icon="save">Simpan angka</x-dk.button></div>
        </form>
    @endif

    {{-- Buku tamu --}}
    @if ($tab === 'bukutamu')
        <form class="ad-card" wire:submit="simpanBukuTamu">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="notebook-pen" :size="18" /> Buku tamu</h2></div>
            <div class="ad-card__body ad-form">
                <x-ad.check model="fotoWajib">Wajibkan foto diri tamu (kamera atau unggah foto)</x-ad.check>
                <p class="ad-help m-0">Kamera di ponsel hanya bisa dibuka bila situs diakses lewat HTTPS. Tanpa HTTPS, tamu tetap bisa memotret lewat tombol <em>Unggah foto</em> yang membuka kamera ponsel.</p>
                <p class="ad-help m-0">Kode kunjungan 4 angka diatur di halaman <a class="underline" href="{{ route('admin.kode') }}" wire:navigate>Kode kunjungan</a>.</p>
            </div>
            <div class="ad-card__foot"><span></span><x-dk.button type="submit" variant="primary" icon="save">Simpan</x-dk.button></div>
        </form>
    @endif
</div>
