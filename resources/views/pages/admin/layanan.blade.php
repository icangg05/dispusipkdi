<?php

use App\Livewire\Concerns\ReordersRecords;
use App\Models\Service;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Layanan')] class extends Component
{
    use ReordersRecords;

    public bool $showForm = false;

    public ?int $editId = null;

    public string $icon = 'book-open';

    public string $title = '';

    public string $text = '';

    public string $link_label = '';

    public string $href = '';

    public bool $is_featured = false;

    public bool $is_active = true;

    public function tambah(): void
    {
        $this->reset('editId', 'icon', 'title', 'text', 'link_label', 'href', 'is_featured', 'is_active');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ubah(int $id): void
    {
        $service = Service::query()->findOrFail($id);
        $this->editId = $service->id;
        $this->icon = $service->icon;
        $this->title = $service->title;
        $this->text = $service->text;
        $this->link_label = (string) $service->link_label;
        $this->href = (string) $service->href;
        $this->is_featured = $service->is_featured;
        $this->is_active = $service->is_active;
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
            'icon' => ['required', Rule::in(config('dispusip.ikon_pilihan'))],
            'title' => ['required', 'string', 'max:60'],
            'text' => ['required', 'string', 'max:140'],
            'link_label' => ['nullable', 'string', 'max:40'],
            'href' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/|#|mailto:|tel:)/'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ], [
            'title.required' => 'Tulis nama layanan.',
            'text.required' => 'Tulis deskripsi singkat.',
            'text.max' => 'Deskripsi paling panjang 140 karakter.',
            'href.regex' => 'Tautan diawali / (halaman situs ini) atau https://.',
        ]);

        $data['link_label'] = $data['link_label'] ?: null;
        $data['href'] = $data['href'] ?: null;

        if ($data['is_featured']) {
            // One featured card per grid.
            Service::query()->when($this->editId, fn ($q) => $q->whereKeyNot($this->editId))->update(['is_featured' => false]);
        }

        if ($this->editId) {
            Service::query()->findOrFail($this->editId)->update($data);
        } else {
            Service::query()->create($data + ['sort' => (int) Service::query()->max('sort') + 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Layanan disimpan.');
    }

    public function pindah(int $id, int $arah): void
    {
        $this->moveRecord(Service::query()->findOrFail($id), $arah);
    }

    public function hapus(int $id): void
    {
        Service::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Layanan dihapus.');
    }

    public function with(): array
    {
        return ['services' => Service::query()->orderBy('sort')->orderBy('id')->get()];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Layanan</h1>
            <p>Kartu layanan di Beranda dan halaman Profil. Satu kartu dapat ditonjolkan (biasanya Buku Tamu Digital); label tautan berupa kata kerja, misalnya <em>Lihat jadwal</em>.</p>
        </div>
        <div class="pnl-pagehead__actions"><x-dk.button variant="primary" icon="plus" wire:click="tambah">Tambah layanan</x-dk.button></div>
    </div>

    <div class="pnl-card">
        @if ($services->isEmpty())
            <x-ad.empty icon="layers" title="Belum ada layanan" />
        @else
            <div class="pnl-tablewrap">
                <table class="pnl-table">
                    <thead><tr><th>Urutan</th><th>Layanan</th><th>Tautan</th><th>Status</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr wire:key="svc-{{ $service->id }}">
                                <td><x-ad.sort-buttons :id="$service->id" :first="$loop->first" :last="$loop->last" /></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span @class(['dk-tile dk-tile--sm', 'dk-tile--lentera' => $service->is_featured])><x-dk.icon :name="$service->icon" :size="18" /></span>
                                        <span><strong>{{ $service->title }}</strong><small>{{ $service->text }}</small></span>
                                    </div>
                                </td>
                                <td><span class="text-[13px]">{{ $service->link_label ?: 'Selengkapnya' }}</span><small class="font-mono">{{ $service->href ?: '—' }}</small></td>
                                <td class="whitespace-nowrap">
                                    <span @class(['pnl-tag', 'pnl-tag--on' => $service->is_active])>{{ $service->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                                    @if ($service->is_featured)<span class="pnl-tag pnl-tag--new">Ditonjolkan</span>@endif
                                </td>
                                <td>
                                    <div class="pnl-actions">
                                        <button type="button" class="dk-iconbtn" wire:click="ubah({{ $service->id }})" aria-label="Ubah"><x-dk.icon name="pencil" :size="17" /></button>
                                        <button type="button" class="dk-iconbtn pnl-iconbtn--danger" wire:click="hapus({{ $service->id }})" wire:confirm="Hapus layanan {{ $service->title }}?" aria-label="Hapus"><x-dk.icon name="trash-2" :size="17" /></button>
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
        <x-ad.modal :title="$editId ? 'Ubah layanan' : 'Tambah layanan'" size="wide">
            <form id="form-layanan" class="pnl-form" wire:submit="simpan">
                <div class="pnl-grid pnl-grid--2">
                    <x-ad.field label="Nama layanan" for="title" error="title"><input id="title" class="pnl-input" wire:model="title" maxlength="60"></x-ad.field>
                    <x-ad.field label="Label tautan" for="link_label" error="link_label" optional><input id="link_label" class="pnl-input" wire:model="link_label" maxlength="40" placeholder="Contoh: Lihat jadwal"></x-ad.field>
                </div>
                <x-ad.field label="Deskripsi singkat" for="text" error="text" help="Paling panjang 140 karakter."><textarea id="text" class="pnl-textarea" rows="2" maxlength="140" wire:model="text"></textarea></x-ad.field>
                <x-ad.field label="Tautan" for="href" error="href" optional help="Halaman situs ini diawali <code>/</code>, misalnya <code>/buku-tamu</code> atau <code>/kontak</code>; situs lain diawali <code>https://</code>."><input id="href" class="pnl-input font-mono" wire:model="href" maxlength="255"></x-ad.field>
                <x-ad.field label="Ikon" error="icon"><x-ad.icon-select model="icon" /></x-ad.field>
                <div class="flex flex-wrap gap-6">
                    <x-ad.check model="is_featured">Tonjolkan kartu ini (hanya satu)</x-ad.check>
                    <x-ad.check model="is_active">Tampilkan di situs</x-ad.check>
                </div>
            </form>
            <x-slot:footer>
                <x-dk.button variant="secondary" wire:click="tutup">Batal</x-dk.button>
                <x-dk.button type="submit" form="form-layanan" variant="primary" icon="save">Simpan</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
