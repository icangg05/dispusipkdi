<?php

use App\Enums\MediaType;
use App\Livewire\Concerns\ReordersRecords;
use App\Models\MediaItem;
use App\Support\Media;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] #[Title('Galeri')] class extends Component
{
    use ReordersRecords;
    use WithFileUploads;

    public string $jenis = 'semua';

    public bool $showForm = false;

    public ?int $editId = null;

    public string $type = 'foto';

    public string $title = '';

    public string $youtube_url = '';

    public string $duration = '';

    public string $taken_at = '';

    public bool $is_published = true;

    public bool $pakaiPoster = false;

    public string $poster_line1 = '';

    public string $poster_line2 = '';

    public string $poster_sub = '';

    public string $poster_tone = 'laut';

    public $gambar = null;

    public ?string $gambarLama = null;

    public function tambah(string $type = 'foto'): void
    {
        $this->reset('editId', 'title', 'youtube_url', 'duration', 'is_published', 'pakaiPoster', 'poster_line1', 'poster_line2', 'poster_sub', 'poster_tone', 'gambar', 'gambarLama');
        $this->type = $type === 'video' ? 'video' : 'foto';
        $this->taken_at = today()->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ubah(int $id): void
    {
        $item = MediaItem::query()->findOrFail($id);
        $this->editId = $item->id;
        $this->type = $item->type->value;
        $this->title = $item->title;
        $this->youtube_url = (string) $item->youtube_url;
        $this->duration = (string) $item->duration;
        $this->taken_at = (string) $item->taken_at?->toDateString();
        $this->is_published = $item->is_published;
        $this->pakaiPoster = $item->hasPoster();
        $this->poster_line1 = (string) $item->poster_line1;
        $this->poster_line2 = (string) $item->poster_line2;
        $this->poster_sub = (string) $item->poster_sub;
        $this->poster_tone = $item->poster_tone ?: 'laut';
        $this->gambar = null;
        $this->gambarLama = $item->image;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function tutup(): void
    {
        $this->showForm = false;
    }

    public function simpan(): void
    {
        $butuhGambar = ! $this->pakaiPoster && ! $this->gambarLama && ($this->type === 'foto' || blank($this->youtube_url));

        $data = $this->validate([
            'type' => ['required', Rule::enum(MediaType::class)],
            'title' => ['required', 'string', 'max:120'],
            'youtube_url' => [$this->type === 'video' ? 'required' : 'nullable', 'nullable', 'url', 'regex:~(youtube\.com|youtu\.be)~'],
            'duration' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'taken_at' => ['nullable', 'date'],
            'is_published' => ['boolean'],
            'gambar' => [$butuhGambar ? 'required' : 'nullable', 'image', 'max:4096'],
            'poster_line1' => [$this->pakaiPoster ? 'required' : 'nullable', 'string', 'max:30'],
            'poster_line2' => ['nullable', 'string', 'max:30'],
            'poster_sub' => ['nullable', 'string', 'max:80'],
            'poster_tone' => ['required', Rule::in(['laut', 'lentera'])],
        ], [
            'title.required' => 'Tulis keterangan singkat.',
            'youtube_url.required' => 'Tempel tautan video YouTube.',
            'youtube_url.url' => 'Tautan YouTube tidak valid.',
            'youtube_url.regex' => 'Tautan harus dari YouTube.',
            'duration.regex' => 'Tulis durasi seperti 3:24.',
            'gambar.required' => 'Pilih foto.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar paling besar 4 MB.',
            'poster_line1.required' => 'Tulis baris pertama poster.',
        ]);

        $image = $this->gambarLama;

        if ($this->gambar) {
            $image = Media::store($this->gambar, 'galeri', $image);
        } elseif ($this->pakaiPoster && $image) {
            Media::delete($image);
            $image = null;
        }

        $values = [
            'type' => $data['type'],
            'title' => $data['title'],
            'image' => $image,
            'youtube_url' => $this->type === 'video' ? $data['youtube_url'] : null,
            'duration' => $this->type === 'video' ? ($data['duration'] ?: null) : null,
            'taken_at' => $data['taken_at'] ?: null,
            'is_published' => $data['is_published'],
            'poster_line1' => $this->pakaiPoster ? $data['poster_line1'] : null,
            'poster_line2' => $this->pakaiPoster ? ($data['poster_line2'] ?: null) : null,
            'poster_sub' => $this->pakaiPoster ? ($data['poster_sub'] ?: null) : null,
            'poster_tone' => $this->pakaiPoster ? $data['poster_tone'] : null,
        ];

        if ($this->editId) {
            MediaItem::query()->findOrFail($this->editId)->update($values);
        } else {
            // New media goes to the top of the gallery.
            MediaItem::query()->create($values + ['sort' => (int) MediaItem::query()->min('sort') - 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Media galeri disimpan.');
    }

    public function pindah(int $id, int $arah): void
    {
        $this->moveRecord(MediaItem::query()->findOrFail($id), $arah);
    }

    public function hapus(int $id): void
    {
        $item = MediaItem::query()->findOrFail($id);
        Media::delete($item->image);
        $item->delete();
        $this->showForm = false;
        $this->dispatch('toast', message: 'Media dihapus.');
    }

    public function with(): array
    {
        return [
            'items' => MediaItem::query()
                ->when($this->jenis !== 'semua', fn ($q) => $q->where('type', $this->jenis))
                ->orderBy('sort')->orderBy('id')
                ->get(),
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Galeri foto &amp; video</h1>
            <p>Item paling atas tampil besar di galeri. Video diputar dari YouTube; keterangan dibuat singkat dan spesifik, misalnya <em>Pojok Baca Anak setiap Sabtu</em>.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="secondary" icon="video" wire:click="tambah('video')">Tambah video</x-dk.button>
            <x-dk.button variant="primary" icon="image-plus" wire:click="tambah('foto')">Tambah foto</x-dk.button>
        </div>
    </div>

    <div class="pnl-card">
        <div class="pnl-toolbar">
            <div class="dk-chips" role="tablist" aria-label="Jenis media">
                @foreach (['semua' => 'Semua', 'foto' => 'Foto', 'video' => 'Video'] as $key => $label)
                    <button type="button" role="tab" @class(['dk-chip', 'is-active' => $jenis === $key]) aria-selected="{{ $jenis === $key ? 'true' : 'false' }}" wire:click="$set('jenis', '{{ $key }}')">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        @if ($items->isEmpty())
            <x-ad.empty icon="images" title="Belum ada media" />
        @else
            <div class="pnl-mediagrid">
                @foreach ($items as $item)
                    <div class="pnl-media" wire:key="media-{{ $item->id }}">
                        <div class="pnl-media__img">
                            @if ($item->hasPoster())
                                <x-dk.poster :line1="$item->poster_line1" :line2="$item->poster_line2" :sub="$item->poster_sub" :tone="$item->poster_tone" />
                            @elseif ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy">
                            @endif
                            <span class="pnl-media__badge"><x-dk.badge :tone="$item->isVideo() ? 'lentera' : 'laut'" :icon="$item->isVideo() ? 'film' : 'image'" size="sm" on-image>{{ $item->type->label() }}{{ $item->duration ? ' · '.$item->duration : '' }}</x-dk.badge></span>
                        </div>
                        <div class="pnl-media__body">
                            <strong>{{ $item->title }}</strong>
                            <small>{{ $item->date_label }}{{ $item->is_published ? '' : ' · Disembunyikan' }}</small>
                        </div>
                        <div class="pnl-media__foot">
                            @if ($jenis === 'semua')
                                <x-ad.sort-buttons :id="$item->id" :first="$loop->first" :last="$loop->last" />
                            @else
                                <span></span>
                            @endif
                            <div class="pnl-actions">
                                <button type="button" class="dk-iconbtn" wire:click="ubah({{ $item->id }})" aria-label="Ubah {{ $item->title }}"><x-dk.icon name="pencil" :size="17" /></button>
                                <button type="button" class="dk-iconbtn pnl-iconbtn--danger" wire:click="hapus({{ $item->id }})" wire:confirm="Hapus {{ $item->title }}?" aria-label="Hapus"><x-dk.icon name="trash-2" :size="17" /></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($showForm)
        <x-ad.modal :title="($editId ? 'Ubah ' : 'Tambah ').($type === 'video' ? 'video' : 'foto')" size="wide">
            <form id="form-galeri" class="pnl-form" wire:submit="simpan">
                <div class="dk-tabs !mb-0" role="tablist" aria-label="Jenis media">
                    <button type="button" role="tab" @class(['dk-tab', 'is-active' => $type === 'foto']) wire:click="$set('type', 'foto')">Foto</button>
                    <button type="button" role="tab" @class(['dk-tab', 'is-active' => $type === 'video']) wire:click="$set('type', 'video')">Video YouTube</button>
                </div>
                <x-ad.field label="Keterangan" for="title" error="title"><input id="title" class="pnl-input" wire:model="title" maxlength="120" placeholder="Contoh: Pojok Baca Anak setiap Sabtu"></x-ad.field>
                @if ($type === 'video')
                    <div class="pnl-grid pnl-grid--2">
                        <x-ad.field label="Tautan YouTube" for="youtube_url" error="youtube_url"><input id="youtube_url" type="url" class="pnl-input" wire:model="youtube_url" placeholder="https://www.youtube.com/watch?v=…"></x-ad.field>
                        <x-ad.field label="Durasi" for="duration" error="duration" optional><input id="duration" class="pnl-input" wire:model="duration" placeholder="3:24"></x-ad.field>
                    </div>
                @endif
                <x-ad.field label="Tanggal" for="taken_at" error="taken_at" optional><input id="taken_at" type="date" class="pnl-input max-w-[220px]" wire:model="taken_at"></x-ad.field>

                <x-ad.check model="pakaiPoster" live>Pakai poster tipografis sebagai pengganti gambar</x-ad.check>
                @if ($pakaiPoster)
                    <div class="pnl-grid pnl-grid--main">
                        <div class="pnl-form">
                            <div class="pnl-grid pnl-grid--2">
                                <x-ad.field label="Baris 1" for="poster_line1" error="poster_line1"><input id="poster_line1" class="pnl-input" wire:model.live.debounce.300ms="poster_line1" maxlength="30" placeholder="Bedah"></x-ad.field>
                                <x-ad.field label="Baris 2 (miring)" for="poster_line2" error="poster_line2" optional><input id="poster_line2" class="pnl-input" wire:model.live.debounce.300ms="poster_line2" maxlength="30" placeholder="Buku"></x-ad.field>
                            </div>
                            <x-ad.field label="Keterangan poster" for="poster_sub" error="poster_sub" optional><input id="poster_sub" class="pnl-input" wire:model.live.debounce.300ms="poster_sub" maxlength="80"></x-ad.field>
                            <div class="flex gap-4">
                                <label class="pnl-check"><input type="radio" value="laut" wire:model.live="poster_tone"><span class="dk-tick__box" aria-hidden="true"><x-dk.icon name="check" :size="16" :stroke="3" /></span> Laut</label>
                                <label class="pnl-check"><input type="radio" value="lentera" wire:model.live="poster_tone"><span class="dk-tick__box" aria-hidden="true"><x-dk.icon name="check" :size="16" :stroke="3" /></span> Lentera</label>
                            </div>
                        </div>
                        <div class="pnl-media__img rounded-md">
                            <x-dk.poster :line1="$poster_line1 ?: 'Judul'" :line2="$poster_line2" :sub="$poster_sub" :tone="$poster_tone" />
                        </div>
                    </div>
                @else
                    <x-ad.field :label="$type === 'video' ? 'Gambar sampul' : 'Foto'" error="gambar" :optional="$type === 'video'" :help="$type === 'video' ? 'Bila dikosongkan, dipakai gambar sampul dari YouTube.' : null">
                        <x-ad.image-upload model="gambar" :file="$gambar" :current="\App\Support\Media::url($gambarLama)" />
                    </x-ad.field>
                @endif
                <x-ad.check model="is_published">Tampilkan di situs</x-ad.check>
            </form>
            <x-slot:footer>
                <x-dk.button variant="secondary" wire:click="tutup">Batal</x-dk.button>
                <x-dk.button type="submit" form="form-galeri" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="simpan, gambar">Simpan</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
