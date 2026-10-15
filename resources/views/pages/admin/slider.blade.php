<?php

use App\Livewire\Concerns\ReordersRecords;
use App\Models\Slide;
use App\Support\Media;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] #[Title('Slider beranda')] class extends Component
{
    use ReordersRecords;
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editId = null;

    public string $caption = '';

    public string $kicker = '';

    public string $title = '';

    public string $accent = '';

    public string $text = '';

    public bool $is_active = true;

    public $gambar = null;

    public ?string $gambarLama = null;

    public function tambah(): void
    {
        $this->reset('editId', 'caption', 'kicker', 'title', 'accent', 'text', 'is_active', 'gambar', 'gambarLama');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ubah(int $id): void
    {
        $slide = Slide::query()->findOrFail($id);
        $this->editId = $slide->id;
        $this->caption = $slide->caption;
        $this->kicker = $slide->kicker;
        $this->title = $slide->title;
        $this->accent = (string) $slide->accent;
        $this->text = (string) $slide->text;
        $this->is_active = $slide->is_active;
        $this->gambar = null;
        $this->gambarLama = $slide->image;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function tutup(): void
    {
        $this->showForm = false;
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'caption' => ['required', 'string', 'max:60'],
            'kicker' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:80'],
            'accent' => ['nullable', 'string', 'max:60'],
            'text' => ['nullable', 'string', 'max:200'],
            'is_active' => ['boolean'],
            'gambar' => [$this->gambarLama ? 'nullable' : 'required', 'image', 'max:4096'],
        ], [
            'caption.required' => 'Tulis nama singkat slide (tampil di penanda slide).',
            'kicker.required' => 'Tulis label kecil di atas judul.',
            'title.required' => 'Tulis judul.',
            'gambar.required' => 'Pilih foto latar.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar paling besar 4 MB.',
        ]);

        $image = $this->gambar ? Media::store($this->gambar, 'slider', $this->gambarLama) : $this->gambarLama;
        $values = collect($data)->except('gambar')->all() + ['image' => $image];
        $values['accent'] = $values['accent'] ?: null;
        $values['text'] = $values['text'] ?: null;

        if ($this->editId) {
            Slide::query()->findOrFail($this->editId)->update($values);
        } else {
            Slide::query()->create($values + ['sort' => (int) Slide::query()->max('sort') + 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Slide disimpan.');
    }

    public function pindah(int $id, int $arah): void
    {
        $this->moveRecord(Slide::query()->findOrFail($id), $arah);
    }

    public function hapus(int $id): void
    {
        $slide = Slide::query()->findOrFail($id);
        Media::delete($slide->image);
        $slide->delete();
        $this->dispatch('toast', message: 'Slide dihapus.');
    }

    public function with(): array
    {
        return ['slides' => Slide::query()->orderBy('sort')->orderBy('id')->get()];
    }
};
?>

<div class="ad-grid" style="gap: var(--space-6)">
    <div class="ad-pagehead">
        <div>
            <h1>Slider beranda</h1>
            <p>Foto latar hero berganti setiap 6 detik. Pakai foto lanskap minimal 1920×1080 dengan objek utama di separuh kanan; 3 sampai 5 slide sudah cukup. Judul paling banyak 6 kata, ditambah kata aksen.</p>
        </div>
        <div class="ad-pagehead__actions"><x-dk.button variant="primary" icon="plus" wire:click="tambah">Tambah slide</x-dk.button></div>
    </div>

    <div class="ad-card">
        @if ($slides->isEmpty())
            <x-ad.empty icon="clapperboard" title="Belum ada slide" text="Beranda memakai foto contoh sampai Anda menambah slide." />
        @else
            <div class="ad-tablewrap">
                <table class="ad-table">
                    <thead><tr><th>Urutan</th><th>Slide</th><th>Teks</th><th>Status</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($slides as $slide)
                            <tr wire:key="slide-{{ $slide->id }}">
                                <td><x-ad.sort-buttons :id="$slide->id" :first="$loop->first" :last="$loop->last" /></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img class="ad-thumb !h-[60px] !w-[106px]" src="{{ $slide->image_url }}" alt="" loading="lazy">
                                        <span><strong>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $slide->caption }}</strong><small>{{ $slide->kicker }}</small></span>
                                    </div>
                                </td>
                                <td class="max-w-[420px]"><strong>{{ $slide->title }} <em class="font-serif font-semibold text-lentera-700">{{ $slide->accent }}</em></strong><small class="line-clamp-2">{{ $slide->text }}</small></td>
                                <td><span @class(['ad-tag', 'ad-tag--on' => $slide->is_active])>{{ $slide->is_active ? 'Tampil' : 'Disembunyikan' }}</span></td>
                                <td>
                                    <div class="ad-actions">
                                        <button type="button" class="dk-iconbtn" wire:click="ubah({{ $slide->id }})" aria-label="Ubah"><x-dk.icon name="pencil" :size="17" /></button>
                                        <button type="button" class="dk-iconbtn ad-iconbtn--danger" wire:click="hapus({{ $slide->id }})" wire:confirm="Hapus slide {{ $slide->caption }}?" aria-label="Hapus"><x-dk.icon name="trash-2" :size="17" /></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($showForm)
        <x-ad.modal :title="$editId ? 'Ubah slide' : 'Tambah slide'" size="wide">
            <form id="form-slide" class="ad-form" wire:submit="simpan">
                <x-ad.field label="Foto latar" error="gambar" help="Lanskap, minimal 1920×1080. Teks selalu dilapisi bayangan gelap agar tetap terbaca.">
                    <x-ad.image-upload model="gambar" :file="$gambar" :current="\App\Support\Media::url($gambarLama)" />
                </x-ad.field>
                <div class="ad-grid ad-grid--2">
                    <x-ad.field label="Label kecil (eyebrow)" for="kicker" error="kicker"><input id="kicker" class="ad-input" wire:model="kicker" maxlength="80" placeholder="Contoh: Koleksi perpustakaan"></x-ad.field>
                    <x-ad.field label="Nama penanda slide" for="caption" error="caption"><input id="caption" class="ad-input" wire:model="caption" maxlength="60" placeholder="Contoh: Koleksi Umum"></x-ad.field>
                </div>
                <div class="ad-grid ad-grid--2">
                    <x-ad.field label="Judul" for="title" error="title"><input id="title" class="ad-input" wire:model="title" maxlength="80" placeholder="Ribuan judul siap"></x-ad.field>
                    <x-ad.field label="Kata aksen (huruf miring kuning)" for="accent" error="accent" optional><input id="accent" class="ad-input" wire:model="accent" maxlength="60" placeholder="dibaca"></x-ad.field>
                </div>
                <x-ad.field label="Teks" for="text" error="text" optional help="Paling panjang sekitar 140 karakter."><textarea id="text" class="ad-textarea" rows="2" maxlength="200" wire:model="text"></textarea></x-ad.field>
                <x-ad.check model="is_active">Tampilkan di beranda</x-ad.check>
            </form>
            <x-slot:footer>
                <x-dk.button variant="secondary" wire:click="tutup">Batal</x-dk.button>
                <x-dk.button type="submit" form="form-slide" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="simpan, gambar">Simpan</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
