<?php

use App\Enums\Tone;
use App\Models\Category;
use App\Livewire\Concerns\ReordersRecords;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Kategori berita')] class extends Component
{
    use ReordersRecords;

    public bool $showForm = false;

    public ?int $editId = null;

    public string $name = '';

    public string $slug = '';

    public string $tone = 'laut';

    public string $icon = 'newspaper';

    public function tambah(): void
    {
        $this->reset('editId', 'name', 'slug', 'tone', 'icon');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ubah(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->editId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->tone = $category->tone->value;
        $this->icon = $category->icon;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function tutup(): void
    {
        $this->showForm = false;
    }

    public function simpan(): void
    {
        $this->slug = Str::slug($this->slug ?: $this->name);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($this->editId)],
            'tone' => ['required', Rule::enum(Tone::class)],
            'icon' => ['required', Rule::in(config('dispusip.ikon_pilihan'))],
        ], [
            'name.required' => 'Tulis nama kategori.',
            'slug.unique' => 'Alamat ini sudah dipakai kategori lain.',
            'icon.in' => 'Pilih salah satu ikon.',
        ]);

        if ($this->editId) {
            Category::query()->findOrFail($this->editId)->update($data);
        } else {
            Category::query()->create($data + ['sort' => (int) Category::query()->max('sort') + 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Kategori disimpan.');
    }

    public function pindah(int $id, int $arah): void
    {
        $this->moveRecord(Category::query()->findOrFail($id), $arah);
    }

    public function hapus(int $id): void
    {
        $category = Category::query()->withCount('posts')->findOrFail($id);

        if ($category->posts_count > 0) {
            $this->dispatch('toast', message: 'Kategori masih dipakai '.$category->posts_count.' berita. Pindahkan beritanya dulu.');

            return;
        }

        $category->delete();
        $this->dispatch('toast', message: 'Kategori dihapus.');
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->ordered()->withCount('posts')->get(),
            'tones' => Tone::cases(),
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Kategori berita</h1>
            <p>Setiap kategori punya satu warna dan satu ikon agar pembaca cepat membedakan Pengumuman, Kegiatan, dan lainnya. Pakai warna yang sudah ada dan usahakan satu warna hanya untuk satu kategori.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="primary" icon="plus" wire:click="tambah">Tambah kategori</x-dk.button>
        </div>
    </div>

    <div class="pnl-card">
        <div class="pnl-tablewrap">
            <table class="pnl-table">
                <thead><tr><th>Urutan</th><th>Kategori</th><th>Alamat</th><th class="num">Berita</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr wire:key="cat-{{ $category->id }}">
                            <td><x-ad.sort-buttons :id="$category->id" :first="$loop->first" :last="$loop->last" /></td>
                            <td><x-dk.badge :category="$category" /></td>
                            <td class="font-mono text-[13px] text-ink-muted">/berita?kategori={{ $category->slug }}</td>
                            <td class="num">{{ $category->posts_count }}</td>
                            <td>
                                <div class="pnl-actions">
                                    <button type="button" class="dk-iconbtn" wire:click="ubah({{ $category->id }})" aria-label="Ubah {{ $category->name }}"><x-dk.icon name="pencil" :size="17" /></button>
                                    <button type="button" class="dk-iconbtn pnl-iconbtn--danger" wire:click="hapus({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->name }}?" aria-label="Hapus {{ $category->name }}"><x-dk.icon name="trash-2" :size="17" /></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($showForm)
        <x-ad.modal :title="$editId ? 'Ubah kategori' : 'Tambah kategori'">
            <form id="form-kategori" class="pnl-form" wire:submit="simpan">
                <div class="pnl-grid pnl-grid--2">
                    <x-ad.field label="Nama" for="name" error="name"><input id="name" class="pnl-input" wire:model.live.debounce.300ms="name" maxlength="60" placeholder="Contoh: Pengumuman"></x-ad.field>
                    <x-ad.field label="Alamat (slug)" for="slug" error="slug" optional help="Dibuat dari nama bila dikosongkan."><input id="slug" class="pnl-input font-mono" wire:model="slug" maxlength="80"></x-ad.field>
                </div>
                <x-ad.field label="Warna" error="tone">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tones as $t)
                            <label class="pnl-check" wire:key="tone-{{ $t->value }}">
                                <input type="radio" name="tone" value="{{ $t->value }}" wire:model.live="tone">
                                <span class="dk-tick__box" aria-hidden="true"><x-dk.icon name="check" :size="16" :stroke="3" /></span>
                                <span class="dk-badge dk-badge--{{ $t->value }}">{{ $t->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-ad.field>
                <x-ad.field label="Ikon" error="icon"><x-ad.icon-select model="icon" /></x-ad.field>
                <x-ad.field label="Pratinjau">
                    <span><x-dk.badge :tone="$tone" :icon="$icon">{{ $name ?: 'Nama kategori' }}</x-dk.badge></span>
                </x-ad.field>
            </form>
            <x-slot:footer>
                <x-dk.button variant="secondary" wire:click="tutup">Batal</x-dk.button>
                <x-dk.button type="submit" form="form-kategori" variant="primary" icon="save">Simpan</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
