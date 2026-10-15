<?php

use App\Http\Middleware\EnsureKioskDevice;
use App\Services\GuestbookService;
use App\Support\Format;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The guest register sheet. Visitors fill it from their phone (website) or from the front-desk tablet (kiosk).
 * On the website the daily 4-digit visit code is required before the sheet is accepted.
 */
new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $variant = 'default';

    public string $nama = '';

    public string $asal = '';

    public string $kategori = '';

    public string $keperluan = '';

    public string $jk = '';

    public int $jumlah = 1;

    public string $wa = '';

    public bool $setuju = true;

    public string $kode = '';

    /** @var TemporaryUploadedFile|null */
    public $foto = null;

    /** @var array{nama: string, nomor: int, tanggal: string, id: int, foto: bool}|null */
    #[Locked]
    public ?array $tercatat = null;

    public function mount(string $variant = 'default'): void
    {
        $this->variant = $variant === 'kiosk' ? 'kiosk' : 'default';
    }

    public function isKiosk(): bool
    {
        return $this->variant === 'kiosk' && EnsureKioskDevice::isKiosk(request());
    }

    public function catat(GuestbookService $guestbook): void
    {
        $kiosk = $this->isKiosk();
        $fotoWajib = site()->fotoTamuWajib();

        try {
            $data = $this->validate([
                'nama' => ['required', 'string', 'max:100'],
                'asal' => ['nullable', 'string', 'max:120'],
                'kategori' => ['required', Rule::in(config('dispusip.kategori_tamu'))],
                'keperluan' => ['required', Rule::in(config('dispusip.keperluan'))],
                'jk' => ['nullable', Rule::in(config('dispusip.jenis_kelamin'))],
                'jumlah' => ['required', 'integer', 'min:1', 'max:200'],
                'wa' => ['nullable', 'regex:/^[0-9+\-\s().]{8,20}$/'],
                'foto' => [$fotoWajib ? 'required' : 'nullable', 'image', 'max:5120'],
                'setuju' => ['accepted'],
                'kode' => $kiosk ? ['nullable'] : ['required', 'digits:4'],
            ], [
                'nama.required' => 'Tulis nama Anda.',
                'nama.max' => 'Nama terlalu panjang.',
                'kategori.required' => 'Pilih salah satu.',
                'kategori.in' => 'Pilih salah satu.',
                'keperluan.required' => 'Pilih keperluan kunjungan.',
                'keperluan.in' => 'Pilih keperluan kunjungan.',
                'jumlah.*' => 'Jumlah orang antara 1 dan 200.',
                'wa.regex' => 'Periksa lagi nomor WhatsApp ini.',
                'foto.required' => 'Ambil foto diri dengan kamera, atau unggah foto.',
                'foto.image' => 'Berkas foto harus berupa gambar.',
                'foto.max' => 'Ukuran foto paling besar 5 MB.',
                'setuju.accepted' => 'Centang persetujuan ini untuk mencatat kunjungan.',
                'kode.required' => 'Masukkan 4 angka kode kunjungan.',
                'kode.digits' => 'Kode kunjungan terdiri dari 4 angka.',
            ]);

            if (! $kiosk) {
                $check = $guestbook->checkCode($this->kode, request()->ip() ?? 'tanpa-ip');

                if (! $check['ok']) {
                    $this->kode = '';
                    $this->dispatch('kode-salah');

                    throw ValidationException::withMessages(['kode' => $check['message']]);
                }
            }
        } catch (ValidationException $e) {
            $this->focusFirstError();

            throw $e;
        }

        $guest = $guestbook->record(
            [
                'nama' => $data['nama'],
                'asal' => $data['asal'] ?? null,
                'kategori' => $data['kategori'],
                'keperluan' => $data['keperluan'],
                'jenis_kelamin' => $data['jk'] ?? null,
                'jumlah' => $data['jumlah'],
                'whatsapp' => $data['wa'] ?? null,
            ],
            $this->foto,
            $kiosk ? 'kios' : 'web',
            request()->ip(),
            request()->userAgent(),
        );

        session()->put('tamu_foto', $guest->id);

        $this->tercatat = [
            'id' => $guest->id,
            'nama' => Format::givenName($guest->nama),
            'nomor' => $guest->nomor,
            'tanggal' => mb_strtoupper(Format::shortDate($guest->visit_date)),
            'foto' => (bool) $guest->foto,
        ];

        $this->resetExcept('variant', 'tercatat');
        $this->dispatch('tamu-tercatat', id: $guest->id);
    }

    public function ulang(): void
    {
        $this->resetExcept('variant');
        $this->resetValidation();
    }

    protected function focusFirstError(): void
    {
        $this->js("setTimeout(() => \$wire.\$el.querySelector('.has-error [data-err], [aria-invalid=\"true\"]')?.focus({ preventScroll: false }), 30)");
    }

    public function with(): array
    {
        return [
            'nomorBerikut' => $this->tercatat['nomor'] ?? app(GuestbookService::class)->nextNumber(),
            'kiosk' => $this->isKiosk(),
            'fotoWajib' => site()->fotoTamuWajib(),
            'kategoriList' => config('dispusip.kategori_tamu'),
            'keperluanList' => config('dispusip.keperluan'),
            'jkList' => config('dispusip.jenis_kelamin'),
            'ikonPilihan' => config('dispusip.ikon_pilihan_tamu', []),
        ];
    }
};
?>

@php
    $isKioskVariant = $variant === 'kiosk';
    $nomorLabel = str_pad((string) $nomorBerikut, 3, '0', STR_PAD_LEFT);
    $judulTag = $isKioskVariant ? 'h3' : 'h2';
@endphp
<div class="dk-gbwrap">
    @if ($tercatat)
        <div @class(['dk-form', 'dk-form--gb', 'dk-form--kiosk' => $isKioskVariant]) role="status"
            @if ($isKioskVariant) x-data x-init="setTimeout(() => $wire.ulang(), 30000)" @endif>
            <header class="dk-form__head">
                <span class="dk-form__mark" aria-hidden="true"><x-dk.icon name="notebook-pen" :size="22" /></span>
                <div class="dk-form__titles">
                    <{{ $judulTag }} class="dk-form__title">Lembar buku tamu</{{ $judulTag }}>
                    <span class="dk-form__sub">{{ \App\Support\Format::longDate() }}</span>
                </div>
                <span class="dk-form__pill dk-form__pill--ok"><x-dk.icon name="circle-check" :size="13" /> Tercatat</span>
            </header>
            <div class="dk-form__body dk-form__done">
                <div class="dk-gb__stamped">
                    <div class="dk-stamp" aria-hidden="true">
                        <span class="dk-stamp__big">Tercatat</span>
                        <span class="dk-stamp__line">{{ $tercatat['tanggal'] }}</span>
                        <span class="dk-stamp__line">Tamu no. {{ $nomorLabel }}</span>
                    </div>
                    @if ($tercatat['foto'])
                        <div class="dk-gb__pinned"><img src="{{ route('buku-tamu.foto-saya', $tercatat['id']) }}" alt="Foto Anda pada lembar buku tamu"></div>
                    @endif
                </div>
                <p class="dk-form__thanks">Terima kasih, {{ $tercatat['nama'] }}.</p>
                <p class="dk-form__note">Kunjungan Anda tercatat sebagai tamu nomor <strong>{{ $tercatat['nomor'] }}</strong> hari ini. Selamat membaca.</p>
                <x-dk.button variant="secondary" icon="user-plus" wire:click="ulang">Isi untuk tamu berikutnya</x-dk.button>
            </div>
        </div>
    @else
        <form @class(['dk-form', 'dk-form--gb', 'dk-form--kiosk' => $isKioskVariant]) wire:submit="catat" novalidate>
            <header class="dk-form__head">
                <span class="dk-form__mark" aria-hidden="true"><x-dk.icon name="notebook-pen" :size="22" /></span>
                <div class="dk-form__titles">
                    <{{ $judulTag }} class="dk-form__title">Lembar buku tamu</{{ $judulTag }}>
                    <span class="dk-form__sub">{{ \App\Support\Format::longDate() }}</span>
                </div>
                <span class="dk-form__pill"><x-dk.icon name="hash" :size="13" /> Tamu no. {{ $nomorLabel }}</span>
            </header>

            <div class="dk-form__body">
                <div @class(['dk-field', 'has-error' => $errors->has('nama')])>
                    <label class="dk-field__label" for="gb-nama">Nama lengkap</label>
                    <div class="dk-input">
                        <x-dk.icon name="user-round" :size="18" />
                        <input id="gb-nama" data-err="nama" wire:model="nama" autocomplete="name" maxlength="100" placeholder="Nama Anda"
                            @error('nama') aria-invalid="true" aria-describedby="gb-nama-err" @enderror>
                    </div>
                    @error('nama')<span class="dk-field__err" id="gb-nama-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>

                <div @class(['dk-field', 'has-error' => $errors->has('asal')])>
                    <label class="dk-field__label" for="gb-asal">Asal sekolah atau instansi <span class="dk-field__opt">(boleh dikosongkan)</span></label>
                    <div class="dk-input">
                        <x-dk.icon name="building-2" :size="18" />
                        <input id="gb-asal" wire:model="asal" maxlength="120" placeholder="Nama sekolah, kampus, atau instansi">
                    </div>
                    @error('asal')<span class="dk-field__err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>

                <fieldset @class(['dk-field', 'has-error' => $errors->has('kategori')]) @error('kategori') aria-describedby="gb-kategori-err" @enderror>
                    <legend class="dk-field__label">Datang sebagai</legend>
                    <div class="dk-choices" data-err="kategori" tabindex="-1">
                        @foreach ($kategoriList as $opsi)
                            <label class="dk-choice" wire:key="kat-{{ $loop->index }}">
                                <input type="radio" name="gb-kategori" value="{{ $opsi }}" wire:model="kategori">
                                <span class="dk-choice__box" aria-hidden="true"><x-dk.icon name="check" :size="12" :stroke="3.5" /></span>
                                @isset($ikonPilihan[$opsi])<x-dk.icon :name="$ikonPilihan[$opsi]" :size="18" class="dk-choice__icon" />@endisset
                                <span>{{ $opsi }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('kategori')<span class="dk-field__err" id="gb-kategori-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </fieldset>

                <fieldset @class(['dk-field', 'has-error' => $errors->has('keperluan')]) @error('keperluan') aria-describedby="gb-keperluan-err" @enderror>
                    <legend class="dk-field__label">Keperluan</legend>
                    <div class="dk-choices" data-err="keperluan" tabindex="-1">
                        @foreach ($keperluanList as $opsi)
                            <label class="dk-choice" wire:key="kep-{{ $loop->index }}">
                                <input type="radio" name="gb-keperluan" value="{{ $opsi }}" wire:model="keperluan">
                                <span class="dk-choice__box" aria-hidden="true"><x-dk.icon name="check" :size="12" :stroke="3.5" /></span>
                                @isset($ikonPilihan[$opsi])<x-dk.icon :name="$ikonPilihan[$opsi]" :size="18" class="dk-choice__icon" />@endisset
                                <span>{{ $opsi }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('keperluan')<span class="dk-field__err" id="gb-keperluan-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </fieldset>

                <div class="dk-form__split">
                    <fieldset class="dk-field">
                        <legend class="dk-field__label">Jenis kelamin <span class="dk-field__opt">(boleh dikosongkan)</span></legend>
                        <div class="dk-choices">
                            @foreach ($jkList as $opsi)
                                <label class="dk-choice" wire:key="jk-{{ $loop->index }}">
                                    <input type="radio" name="gb-jk" value="{{ $opsi }}" wire:model="jk">
                                    <span class="dk-choice__box" aria-hidden="true"><x-dk.icon name="check" :size="12" :stroke="3.5" /></span>
                                    <span>{{ $opsi }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div @class(['dk-field', 'has-error' => $errors->has('jumlah')]) x-data="{ n: $wire.entangle('jumlah') }">
                        <span class="dk-field__label" id="gb-jml">Jumlah orang</span>
                        <span class="dk-count" role="group" aria-labelledby="gb-jml">
                            <button type="button" @click="n = Math.max(1, n - 1)" :disabled="n <= 1" aria-label="Kurangi satu orang"><x-dk.icon name="minus" :size="18" /></button>
                            <output aria-live="polite" x-text="n">{{ $jumlah }}</output>
                            <button type="button" @click="n = Math.min(200, n + 1)" aria-label="Tambah satu orang"><x-dk.icon name="plus" :size="18" /></button>
                        </span>
                        @error('jumlah')<span class="dk-field__err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                    </div>
                </div>

                <hr class="dk-form__rule">

                <div @class(['dk-field', 'has-error' => $errors->has('wa')])>
                    <label class="dk-field__label" for="gb-wa">Nomor WhatsApp <span class="dk-field__opt">(boleh dikosongkan)</span></label>
                    <div class="dk-input">
                        <x-dk.icon name="phone" :size="18" />
                        <input id="gb-wa" data-err="wa" wire:model="wa" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="contoh: 0812xxxxxxx"
                            aria-describedby="gb-wa-help @error('wa') gb-wa-err @enderror" @error('wa') aria-invalid="true" @enderror>
                    </div>
                    <span class="dk-field__help" id="gb-wa-help">Hanya untuk konfirmasi kunjungan rombongan.</span>
                    @error('wa')<span class="dk-field__err" id="gb-wa-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>

                {{-- Pas foto: the visitor photographs themself with the front camera. --}}
                <div @class(['dk-field', 'has-error' => $errors->has('foto')])>
                    <span class="dk-field__label" id="gb-foto-label">Foto diri @unless ($fotoWajib)<span class="dk-field__opt">(boleh dikosongkan)</span>@endunless</span>
                    <div class="dk-photo" x-data="selfieCamera({ model: 'foto' })" x-on:tamu-tercatat.window="reset()" wire:ignore>
                        <div class="dk-photo__frame" :class="{ 'is-live': state === 'live', 'is-set': !!preview && state !== 'live' }" aria-labelledby="gb-foto-label">
                            <video x-ref="video" x-show="state === 'live' || state === 'starting'" playsinline muted aria-label="Pratinjau kamera" style="display: none"></video>
                            <span class="dk-photo__guide" x-show="state === 'live'" style="display: none" aria-hidden="true"></span>
                            <template x-if="preview && state !== 'live' && state !== 'starting'">
                                <img :src="preview" alt="Foto Anda">
                            </template>
                            <span class="dk-photo__empty" x-show="!preview && state === 'idle'" aria-hidden="true">
                                <x-dk.icon name="scan-face" :size="30" :stroke="1.6" />
                                <b>Pas foto</b>
                                <span>3 × 4</span>
                            </span>
                            <span class="dk-photo__busy" x-show="state === 'uploading'" style="display: none"><span :style="{ width: progress + '%' }"></span></span>
                        </div>
                        <div class="dk-photo__side">
                            <p class="dk-photo__text" x-show="state === 'idle' && !preview">Buka kamera depan dan potret diri Anda. Wajah berada di dalam garis oval.</p>
                            <p class="dk-photo__text" x-show="state === 'live'" style="display: none">Lihat ke kamera, lalu tekan <strong>Ambil foto</strong>.</p>
                            <span class="dk-photo__ok" x-show="state === 'done'" style="display: none"><x-dk.icon name="circle-check" :size="16" /> Foto tersimpan di lembar ini</span>
                            <span class="dk-photo__text" x-show="state === 'uploading'" style="display: none" aria-live="polite">Mengunggah foto… <span x-text="progress + '%'"></span></span>

                            <div class="dk-photo__actions">
                                <template x-if="state === 'idle' && !preview">
                                    <x-dk.button variant="primary" icon="camera" @click="start()">Buka kamera</x-dk.button>
                                </template>
                                <template x-if="state === 'starting'">
                                    <x-dk.button variant="primary" icon="camera" disabled>Membuka kamera…</x-dk.button>
                                </template>
                                <template x-if="state === 'live'">
                                    <x-dk.button variant="primary" icon="camera" @click="capture()">Ambil foto</x-dk.button>
                                </template>
                                <template x-if="state === 'live' && canSwitch">
                                    <x-dk.button variant="secondary" icon="switch-camera" @click="switchCamera()" aria-label="Ganti kamera"></x-dk.button>
                                </template>
                                <template x-if="state === 'live'">
                                    <x-dk.button variant="ghost" @click="cancel()">Batal</x-dk.button>
                                </template>
                                <template x-if="state === 'done'">
                                    <x-dk.button variant="secondary" icon="rotate-ccw" @click="retake()">Ulangi foto</x-dk.button>
                                </template>
                                <label class="dk-btn dk-btn--secondary dk-btn--md" x-show="state === 'idle' || state === 'done'">
                                    <x-dk.icon name="upload" :size="18" /><span>Unggah foto</span>
                                    <input type="file" accept="image/*" capture="user" class="dk-sr" @change="pick($event)">
                                </label>
                            </div>
                            <span class="dk-field__err" x-show="error" x-text="error" style="display: none" role="alert"></span>
                        </div>
                    </div>
                    @error('foto')<span class="dk-field__err" data-err="foto" tabindex="-1"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>

                <hr class="dk-form__rule">

                @unless ($kiosk)
                    <div @class(['dk-field', 'has-error' => $errors->has('kode')]) x-data="visitCode()" x-on:kode-salah.window="clear()" x-on:tamu-tercatat.window="clear()">
                        <span class="dk-field__label" id="gb-kode-label">Kode kunjungan hari ini</span>
                        <input type="hidden" x-ref="value" wire:model="kode">
                        <div class="dk-kode" role="group" aria-labelledby="gb-kode-label" aria-describedby="gb-kode-help" wire:ignore>
                            @for ($i = 0; $i < 4; $i++)
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                    x-ref="d{{ $i }}" :value="digits[{{ $i }}]" @input="input({{ $i }}, $event)" @keydown="keydown({{ $i }}, $event)" @paste="paste($event)"
                                    @if ($i === 0) data-err="kode" @endif aria-label="Angka ke-{{ $i + 1 }} dari 4">
                            @endfor
                        </div>
                        <span class="dk-field__help" id="gb-kode-help">Empat angka yang tertera di meja layanan hari ini. Tanyakan kepada petugas bila belum melihatnya.</span>
                        @error('kode')<span class="dk-field__err" role="alert"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                    </div>
                @else
                    <span class="dk-kiosk-trusted"><x-dk.icon name="shield-check" :size="18" /> Tablet meja layanan, tanpa kode kunjungan.</span>
                @endunless

                <div @class(['dk-field', 'has-error' => $errors->has('setuju')])>
                    <label class="dk-check">
                        <input type="checkbox" data-err="setuju" wire:model="setuju">
                        <span class="dk-choice__box" aria-hidden="true"><x-dk.icon name="check" :size="12" :stroke="3.5" /></span>
                        <span>Data ini hanya dipakai untuk statistik layanan {{ site()->get('identitas.singkatan') }} {{ site()->get('identitas.kota') }}.</span>
                    </label>
                    @error('setuju')<span class="dk-field__err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>

                <x-dk.button type="submit" variant="primary" size="lg" icon="notebook-pen" block wire:loading.attr="disabled" wire:target="catat, foto">
                    <span wire:loading.remove wire:target="catat">Catat kunjungan</span>
                    <span wire:loading wire:target="catat">Mencatat…</span>
                </x-dk.button>
            </div>
        </form>
    @endif
</div>
