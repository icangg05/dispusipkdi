<?php

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Rules\WhatsappNumber;
use App\Support\Format;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A card for asking the librarians: boxed fields with leading icons and topic chips.
 * The answer goes back by WhatsApp, so a WhatsApp number is required.
 */
new class extends Component
{
    public const MAKS = 1000;

    public string $nama = '';

    public string $kontak = '';

    public string $topik = '';

    public string $isi = '';

    /** Honeypot: people never see or fill this field. */
    public string $situs = '';

    /** @var array{nama: string, kontak: string, topik: string, isi: string}|null */
    #[Locked]
    public ?array $terkirim = null;

    public function kirim(): void
    {
        $limiterKey = 'pertanyaan:'.request()->ip();

        try {
            if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
                throw ValidationException::withMessages(['isi' => 'Anda sudah mengirim beberapa pertanyaan. Coba lagi dalam satu jam, atau hubungi kami lewat WhatsApp.']);
            }

            $data = $this->validate([
                'nama' => ['required', 'string', 'max:100'],
                'kontak' => ['required', 'string', 'max:20', new WhatsappNumber],
                'topik' => ['required', Rule::in(array_keys(config('dispusip.topik_pertanyaan')))],
                'isi' => ['required', 'string', 'min:10', 'max:'.self::MAKS],
            ], [
                'nama.required' => 'Tulis nama Anda.',
                'kontak.required' => 'Tulis nomor WhatsApp untuk menerima jawaban.',
                'kontak.max' => 'Tulis nomor WhatsApp yang aktif, misalnya 081234567890.',
                'topik.required' => 'Pilih topik pertanyaan.',
                'topik.in' => 'Pilih topik pertanyaan.',
                'isi.required' => 'Tulis pertanyaan Anda, minimal 10 karakter.',
                'isi.min' => 'Tulis pertanyaan Anda, minimal 10 karakter.',
                'isi.max' => 'Pertanyaan paling panjang '.self::MAKS.' karakter.',
            ]);
        } catch (ValidationException $e) {
            $this->js("setTimeout(() => \$wire.\$el.querySelector('.has-error [data-err]')?.focus(), 30)");

            throw $e;
        }

        if (blank($this->situs)) {
            Question::query()->create([
                'nama' => trim($data['nama']),
                'kontak' => trim($data['kontak']),
                'topik' => $data['topik'],
                'isi' => trim($data['isi']),
                'status' => QuestionStatus::Baru,
                'ip_address' => request()->ip(),
            ]);

            RateLimiter::hit($limiterKey, 3600);
        }

        $this->terkirim = [
            'nama' => Format::givenName($data['nama']),
            'kontak' => trim($data['kontak']),
            'topik' => $data['topik'],
            'isi' => Str::ucfirst(trim($data['isi'])),
        ];

        $this->reset('nama', 'kontak', 'topik', 'isi', 'situs');
    }

    public function ulang(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    public function with(): array
    {
        $identitas = site()->identitas();

        return [
            'maks' => self::MAKS,
            'topikList' => config('dispusip.topik_pertanyaan'),
            'pengirim' => $identitas['singkatan'].' '.$identitas['kota'],
        ];
    }
};
?>

<div class="dk-form">
    <header class="dk-form__head">
        <span class="dk-form__mark" aria-hidden="true"><x-dk.icon name="book-open" :size="22" /></span>
        <div class="dk-form__titles">
            <h3 class="dk-form__title">Pertanyaan untuk pustakawan</h3>
            <span class="dk-form__sub">{{ $pengirim }}</span>
        </div>
        @if ($terkirim)
            <span class="dk-form__pill dk-form__pill--ok"><x-dk.icon name="circle-check" :size="13" /> Terkirim</span>
        @else
            <span class="dk-form__pill"><x-dk.icon name="clock" :size="13" /> Dijawab pada jam layanan</span>
        @endif
    </header>

    @if ($terkirim)
        <div class="dk-form__body dk-form__done" role="status">
            <p class="dk-form__thanks">Terima kasih, {{ $terkirim['nama'] }}.</p>
            <p class="dk-form__note">Pertanyaan Anda tentang {{ mb_strtolower($terkirim['topik']) }} sudah kami terima. Jawaban kami kirim lewat WhatsApp ke <strong>{{ $terkirim['kontak'] }}</strong> pada jam layanan.</p>
            <blockquote class="dk-form__quote">{{ $terkirim['isi'] }}</blockquote>
            <x-dk.button variant="secondary" icon="pencil" wire:click="ulang">Tulis pertanyaan lain</x-dk.button>
        </div>
    @else
        <form class="dk-form__body" wire:submit="kirim" novalidate x-data="{ isi: $wire.entangle('isi') }">
            <div @class(['dk-field', 'has-error' => $errors->has('nama')])>
                <label class="dk-field__label" for="qf-nama">Nama</label>
                <div class="dk-input">
                    <x-dk.icon name="user-round" :size="18" />
                    <input id="qf-nama" data-err="nama" wire:model="nama" autocomplete="name" maxlength="100" placeholder="Nama Anda" @error('nama') aria-invalid="true" aria-describedby="qf-nama-err" @enderror>
                </div>
                @error('nama')<span class="dk-field__err" id="qf-nama-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
            </div>

            <div @class(['dk-field', 'has-error' => $errors->has('kontak')])>
                <label class="dk-field__label" for="qf-kontak">Nomor WhatsApp</label>
                <div class="dk-input">
                    <x-dk.icon name="phone" :size="18" />
                    <input id="qf-kontak" type="tel" data-err="kontak" wire:model="kontak" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="contoh: 081234567890" aria-describedby="qf-kontak-help @error('kontak') qf-kontak-err @enderror" @error('kontak') aria-invalid="true" @enderror>
                </div>
                <span class="dk-field__help" id="qf-kontak-help">Jawaban kami kirim lewat WhatsApp ke nomor ini.</span>
                @error('kontak')<span class="dk-field__err" id="qf-kontak-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
            </div>

            <fieldset @class(['dk-field', 'has-error' => $errors->has('topik')]) @error('topik') aria-describedby="qf-topik-err" @enderror>
                <legend class="dk-field__label">Topik</legend>
                <div class="dk-choices" data-err="topik" tabindex="-1">
                    @foreach ($topikList as $opsi => $ikon)
                        <label class="dk-choice" wire:key="topik-{{ $loop->index }}">
                            <input type="radio" name="qf-topik" value="{{ $opsi }}" wire:model="topik">
                            <span class="dk-choice__box" aria-hidden="true"><x-dk.icon name="check" :size="12" :stroke="3.5" /></span>
                            <x-dk.icon :name="$ikon" :size="18" class="dk-choice__icon" />
                            <span>{{ $opsi }}</span>
                        </label>
                    @endforeach
                </div>
                @error('topik')<span class="dk-field__err" id="qf-topik-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
            </fieldset>

            <hr class="dk-form__rule">

            <div @class(['dk-field', 'has-error' => $errors->has('isi')])>
                <label class="dk-field__label" for="qf-isi">Pertanyaan</label>
                <div class="dk-input dk-input--area">
                    <x-dk.icon name="pencil" :size="18" />
                    <textarea id="qf-isi" data-err="isi" rows="5" maxlength="{{ $maks }}" x-model="isi" placeholder="Tuliskan pertanyaan Anda di sini…" aria-describedby="qf-isi-n @error('isi') qf-isi-err @enderror" @error('isi') aria-invalid="true" @enderror></textarea>
                </div>
                <span class="dk-field__count" id="qf-isi-n"><span x-text="(isi || '').length">0</span> / {{ $maks }} karakter</span>
                @error('isi')<span class="dk-field__err" id="qf-isi-err"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
            </div>

            <div class="dk-sr" aria-hidden="true">
                <label for="qf-situs">Situs web</label>
                <input id="qf-situs" type="text" wire:model="situs" tabindex="-1" autocomplete="off">
            </div>

            <x-dk.button type="submit" variant="primary" size="lg" icon="send" block wire:loading.attr="disabled" wire:target="kirim">Kirim pertanyaan</x-dk.button>
        </form>
    @endif
</div>
