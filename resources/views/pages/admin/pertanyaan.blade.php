<?php

use App\Enums\QuestionStatus;
use App\Models\Question;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Pertanyaan')] class extends Component
{
    use WithPagination;

    #[Url(except: 'baru')]
    public string $status = 'baru';

    #[Url(as: 'buka', except: null)]
    public ?int $buka = null;

    public string $catatan = '';

    public function mount(): void
    {
        if ($this->buka) {
            $this->catatan = (string) Question::query()->find($this->buka)?->catatan;
        }
    }

    public function filter(string $status): void
    {
        $this->status = in_array($status, ['baru', 'dijawab', 'semua'], true) ? $status : 'baru';
        $this->resetPage();
    }

    public function lihat(int $id): void
    {
        $this->buka = $id;
        $this->catatan = (string) Question::query()->findOrFail($id)->catatan;
        $this->resetValidation();
    }

    public function tutup(): void
    {
        $this->buka = null;
        $this->catatan = '';
    }

    public function tandai(int $id, bool $dijawab): void
    {
        $this->validate(['catatan' => ['nullable', 'string', 'max:2000']]);

        Question::query()->findOrFail($id)->update([
            'status' => $dijawab ? QuestionStatus::Dijawab : QuestionStatus::Baru,
            'answered_at' => $dijawab ? now() : null,
            'catatan' => $this->catatan ?: null,
        ]);

        $this->dispatch('toast', message: $dijawab ? 'Pertanyaan ditandai sudah dijawab.' : 'Pertanyaan dikembalikan ke belum dijawab.');
        $this->tutup();
    }

    public function hapus(int $id): void
    {
        Question::query()->findOrFail($id)->delete();
        $this->tutup();
        $this->dispatch('toast', message: 'Pertanyaan dihapus.');
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    public function with(): array
    {
        return [
            'questions' => Question::query()
                ->when($this->status !== 'semua', fn ($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(20),
            'counts' => [
                'baru' => Question::query()->where('status', QuestionStatus::Baru)->count(),
                'dijawab' => Question::query()->where('status', QuestionStatus::Dijawab)->count(),
                'semua' => Question::query()->count(),
            ],
            'detail' => $this->buka ? Question::query()->find($this->buka) : null,
        ];
    }
};
?>

<div class="ad-grid" style="gap: var(--space-6)">
    <div class="ad-pagehead">
        <div>
            <h1>Pertanyaan untuk pustakawan</h1>
            <p>Pertanyaan dari formulir <em>Ada pertanyaan?</em> di situs. Balas lewat WhatsApp atau email, lalu tandai sudah dijawab.</p>
        </div>
    </div>

    <div class="ad-card">
        <div class="ad-toolbar">
            <div class="dk-chips" role="tablist" aria-label="Status pertanyaan">
                @foreach (['baru' => ['Belum dijawab', 'inbox'], 'dijawab' => ['Sudah dijawab', 'circle-check'], 'semua' => ['Semua', 'list']] as $key => [$label, $icon])
                    <button type="button" role="tab" @class(['dk-chip', 'is-active' => $status === $key]) aria-selected="{{ $status === $key ? 'true' : 'false' }}" wire:click="filter('{{ $key }}')">
                        <x-dk.icon :name="$icon" :size="15" /> {{ $label }} <span class="dk-chip__n">{{ $counts[$key] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($questions->isEmpty())
            <x-ad.empty icon="mail-open" title="Tidak ada pertanyaan di sini" />
        @else
            <div class="ad-tablewrap">
                <table class="ad-table">
                    <thead><tr><th>Penanya</th><th>Topik</th><th>Pertanyaan</th><th>Masuk</th><th>Status</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($questions as $q)
                            <tr wire:key="q-{{ $q->id }}">
                                <td><strong>{{ $q->nama }}</strong><small>{{ $q->kontak }}</small></td>
                                <td class="whitespace-nowrap">{{ $q->topik }}</td>
                                <td class="max-w-[420px]"><span class="line-clamp-2">{{ $q->isi }}</span></td>
                                <td class="whitespace-nowrap">{{ \App\Support\Format::shortDate($q->created_at) }}<small>{{ $q->created_at->format('H.i') }} WITA</small></td>
                                <td><span @class(['ad-tag', 'ad-tag--new' => $q->status === \App\Enums\QuestionStatus::Baru, 'ad-tag--on' => $q->status === \App\Enums\QuestionStatus::Dijawab])>{{ $q->status->label() }}</span></td>
                                <td><div class="ad-actions"><x-dk.button variant="secondary" size="sm" wire:click="lihat({{ $q->id }})">Buka</x-dk.button></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ad-pager">{{ $questions->links() }}</div>
        @endif
    </div>

    @if ($detail)
        @php
            $balasan = 'Halo '.\App\Support\Format::givenName($detail->nama).', terima kasih atas pertanyaan Anda tentang '.mb_strtolower($detail->topik).' kepada '.site()->get('identitas.singkatan').' '.site()->get('identitas.kota').'. ';
        @endphp
        <x-ad.modal title="Pertanyaan dari {{ $detail->nama }}">
            <div class="grid gap-5">
                <dl class="ad-kv">
                    <dt>Kontak</dt><dd>{{ $detail->kontak }}</dd>
                    <dt>Topik</dt><dd>{{ $detail->topik }}</dd>
                    <dt>Masuk</dt><dd>{{ \App\Support\Format::longDate($detail->created_at) }}, {{ $detail->created_at->format('H.i') }} WITA</dd>
                    @if ($detail->answered_at)<dt>Dijawab</dt><dd>{{ \App\Support\Format::longDate($detail->answered_at) }}</dd>@endif
                </dl>
                <blockquote class="ad-quote">{{ $detail->isi }}</blockquote>
                <div class="flex flex-wrap gap-2">
                    @if ($detail->whatsappLink())
                        <x-dk.button variant="primary" icon="whatsapp" :href="$detail->whatsappLink().'?text='.rawurlencode($balasan)" target="_blank" rel="noopener">Balas lewat WhatsApp</x-dk.button>
                    @endif
                    @if ($detail->isEmail())
                        <x-dk.button variant="primary" icon="mail" :href="'mailto:'.$detail->kontak.'?subject='.rawurlencode('Jawaban pertanyaan: '.$detail->topik).'&body='.rawurlencode($balasan)">Balas lewat email</x-dk.button>
                    @endif
                </div>
                <x-ad.field label="Catatan petugas" for="catatan" error="catatan" optional help="Hanya terlihat di panel admin, misalnya ringkasan jawaban atau siapa yang membalas.">
                    <textarea id="catatan" class="ad-textarea" rows="3" wire:model="catatan"></textarea>
                </x-ad.field>
            </div>
            <x-slot:footer>
                <x-dk.button variant="ghost" icon="trash-2" class="mr-auto !text-danger" wire:click="hapus({{ $detail->id }})" wire:confirm="Hapus pertanyaan ini?">Hapus</x-dk.button>
                @if ($detail->status === \App\Enums\QuestionStatus::Dijawab)
                    <x-dk.button variant="secondary" wire:click="tandai({{ $detail->id }}, false)">Tandai belum dijawab</x-dk.button>
                @else
                    <x-dk.button variant="primary" icon="circle-check" wire:click="tandai({{ $detail->id }}, true)">Tandai sudah dijawab</x-dk.button>
                @endif
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
