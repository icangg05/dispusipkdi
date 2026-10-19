<?php

use App\Livewire\Concerns\ReordersRecords;
use App\Models\Faq;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Tanya jawab')] class extends Component
{
    use ReordersRecords;

    public bool $showForm = false;

    public ?int $editId = null;

    public string $question = '';

    public string $answer = '';

    public bool $is_active = true;

    public function tambah(): void
    {
        $this->reset('editId', 'question', 'answer', 'is_active');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ubah(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);
        $this->editId = $faq->id;
        $this->fill($faq->only(['question', 'answer', 'is_active']));
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
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [
            'question.required' => 'Tulis pertanyaannya.',
            'answer.required' => 'Tulis jawabannya.',
        ]);

        if ($this->editId) {
            Faq::query()->findOrFail($this->editId)->update($data);
        } else {
            Faq::query()->create($data + ['sort' => (int) Faq::query()->max('sort') + 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Tanya jawab disimpan.');
    }

    public function pindah(int $id, int $arah): void
    {
        $this->moveRecord(Faq::query()->findOrFail($id), $arah);
    }

    public function hapus(int $id): void
    {
        Faq::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Tanya jawab dihapus.');
    }

    public function with(): array
    {
        return ['faqs' => Faq::query()->orderBy('sort')->orderBy('id')->get()];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Tanya jawab</h1>
            <p>Daftar <em>Yang sering ditanyakan</em> di Beranda dan halaman Kontak. Tulis pertanyaan seperti yang diucapkan warga; jawab langsung dalam 1 sampai 3 kalimat.</p>
        </div>
        <div class="pnl-pagehead__actions"><x-dk.button variant="primary" icon="plus" wire:click="tambah">Tambah tanya jawab</x-dk.button></div>
    </div>

    <div class="pnl-card">
        @if ($faqs->isEmpty())
            <x-ad.empty icon="message-circle" title="Belum ada tanya jawab" />
        @else
            <div class="pnl-tablewrap">
                <table class="pnl-table">
                    <thead><tr><th>Urutan</th><th>Pertanyaan &amp; jawaban</th><th>Status</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($faqs as $faq)
                            <tr wire:key="faq-{{ $faq->id }}">
                                <td><x-ad.sort-buttons :id="$faq->id" :first="$loop->first" :last="$loop->last" /></td>
                                <td class="max-w-[640px]"><strong>{{ $faq->question }}</strong><small class="line-clamp-2">{{ $faq->answer }}</small></td>
                                <td><span @class(['pnl-tag', 'pnl-tag--on' => $faq->is_active])>{{ $faq->is_active ? 'Tampil' : 'Disembunyikan' }}</span></td>
                                <td>
                                    <div class="pnl-actions">
                                        <button type="button" class="dk-iconbtn" wire:click="ubah({{ $faq->id }})" aria-label="Ubah"><x-dk.icon name="pencil" :size="17" /></button>
                                        <button type="button" class="dk-iconbtn pnl-iconbtn--danger" wire:click="hapus({{ $faq->id }})" wire:confirm="Hapus tanya jawab ini?" aria-label="Hapus"><x-dk.icon name="trash-2" :size="17" /></button>
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
        <x-ad.modal :title="$editId ? 'Ubah tanya jawab' : 'Tambah tanya jawab'">
            <form id="form-faq" class="pnl-form" wire:submit="simpan">
                <x-ad.field label="Pertanyaan" for="question" error="question"><input id="question" class="pnl-input" wire:model="question" maxlength="255" placeholder="Contoh: Apakah meminjam buku dikenakan biaya?"></x-ad.field>
                <x-ad.field label="Jawaban" for="answer" error="answer"><textarea id="answer" class="pnl-textarea" rows="4" wire:model="answer" maxlength="2000"></textarea></x-ad.field>
                <x-ad.check model="is_active">Tampilkan di situs</x-ad.check>
            </form>
            <x-slot:footer>
                <x-dk.button variant="secondary" wire:click="tutup">Batal</x-dk.button>
                <x-dk.button type="submit" form="form-faq" variant="primary" icon="save">Simpan</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
