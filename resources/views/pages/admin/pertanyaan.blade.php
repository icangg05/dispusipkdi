<?php

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Support\SiteSettings;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Pertanyaan')] class extends Component
{
    use WithPagination;

    public const MAKS_TEMPLATE = 8;

    #[Url(except: 'baru')]
    public string $status = 'baru';

    #[Url(as: 'buka', except: null)]
    public ?int $buka = null;

    /** The WhatsApp reply; it is kept as the question's note once sent. */
    public string $pesan = '';

    /** Index of the template the reply was started from. */
    public ?int $template = null;

    public bool $kelolaTemplate = false;

    /** @var list<array{judul: string, isi: string}> */
    public array $templates = [];

    public function mount(): void
    {
        if ($this->buka) {
            $this->siapkanPesan(Question::query()->find($this->buka));
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
        $this->siapkanPesan(Question::query()->findOrFail($id));
        $this->resetValidation();
    }

    /**
     * Start from the reply already sent, or from the first template for a question nobody has answered.
     */
    protected function siapkanPesan(?Question $question): void
    {
        $this->template = null;
        $this->pesan = (string) $question?->catatan;

        if ($question && $this->pesan === '' && site()->templateBalasan() !== []) {
            $this->pakaiTemplate(0);
        }
    }

    public function pakaiTemplate(int $i): void
    {
        $template = site()->templateBalasan()[$i] ?? null;
        $question = $this->buka ? Question::query()->find($this->buka) : null;

        if ($template && $question) {
            $this->pesan = $question->fillTemplate($template['isi'], (string) auth()->user()?->name);
            $this->template = $i;
            $this->resetValidation('pesan');
        }
    }

    public function tutup(): void
    {
        $this->buka = null;
        $this->pesan = '';
        $this->template = null;
    }

    /**
     * Mark the question answered, keep the message as its note, and return the wa.me link that opens the chat with
     * the message typed in. Questions without a WhatsApp number return null.
     */
    public function kirim(int $id): ?string
    {
        $question = Question::query()->findOrFail($id);

        if ($question->whatsappLink() === null) {
            return null;
        }

        $this->validate(['pesan' => ['required', 'string', 'max:2000']], [
            'pesan.required' => 'Tulis pesan balasan terlebih dahulu.',
            'pesan.max' => 'Pesan paling panjang 2000 karakter.',
        ]);

        $question->update([
            'status' => QuestionStatus::Dijawab,
            'answered_at' => now(),
            'catatan' => $this->pesan,
        ]);

        $this->dispatch('toast', message: 'Pertanyaan ditandai sudah dijawab. Tinggal kirim pesannya di WhatsApp.');
        $this->tutup();

        return $question->whatsappLink($question->catatan);
    }

    /**
     * Change the status by hand: to reopen an answered question, or for older questions that left an email.
     */
    public function tandai(int $id, bool $dijawab): void
    {
        $this->validate(['pesan' => ['nullable', 'string', 'max:2000']]);

        Question::query()->findOrFail($id)->update([
            'status' => $dijawab ? QuestionStatus::Dijawab : QuestionStatus::Baru,
            'answered_at' => $dijawab ? now() : null,
            'catatan' => $this->pesan,
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

    public function bukaTemplate(): void
    {
        $this->tutup();
        $this->templates = site()->templateBalasan() ?: [['judul' => '', 'isi' => '']];
        $this->kelolaTemplate = true;
        $this->resetValidation();
    }

    public function tutupTemplate(): void
    {
        $this->kelolaTemplate = false;
        $this->templates = [];
        $this->resetValidation();
    }

    public function tambahTemplate(): void
    {
        if (count($this->templates) < self::MAKS_TEMPLATE) {
            $this->templates[] = ['judul' => '', 'isi' => ''];
        }
    }

    public function hapusTemplate(int $i): void
    {
        unset($this->templates[$i]);
        $this->templates = array_values($this->templates);
    }

    public function simpanTemplate(SiteSettings $settings): void
    {
        $this->validate([
            'templates' => ['array', 'max:'.self::MAKS_TEMPLATE],
            'templates.*.judul' => ['required', 'string', 'max:40'],
            'templates.*.isi' => ['required', 'string', 'max:1500'],
        ], [
            'templates.*.judul.required' => 'Beri judul template ini.',
            'templates.*.judul.max' => 'Judul paling panjang 40 karakter.',
            'templates.*.isi.required' => 'Tulis isi template ini.',
            'templates.*.isi.max' => 'Isi template paling panjang 1500 karakter.',
        ]);

        $settings->put('balasan', ['template' => array_map(fn (array $t): array => [
            'judul' => Str::ucfirst(trim($t['judul'])),
            'isi' => Str::ucfirst(trim($t['isi'])),
        ], array_values($this->templates))]);

        $this->dispatch('toast', message: 'Template balasan disimpan.');
        $this->tutupTemplate();
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
            'templateList' => site()->templateBalasan(),
            'placeholders' => Question::PLACEHOLDERS,
            'maksTemplate' => self::MAKS_TEMPLATE,
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Pertanyaan untuk pustakawan</h1>
            <p>Pertanyaan dari formulir <em>Ada pertanyaan?</em> di situs. Buka pertanyaan, pilih template, lalu kirim lewat WhatsApp. Pertanyaan otomatis ditandai sudah dijawab.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="secondary" icon="message-square-text" wire:click="bukaTemplate">Template balasan</x-dk.button>
        </div>
    </div>

    <div class="pnl-card">
        <div class="pnl-toolbar">
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
            <div class="pnl-tablewrap">
                <table class="pnl-table">
                    <thead><tr><th>Penanya</th><th>Topik</th><th>Pertanyaan</th><th>Masuk</th><th>Status</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($questions as $q)
                            <tr wire:key="q-{{ $q->id }}">
                                <td><strong>{{ $q->nama }}</strong><small>{{ $q->kontak }}</small></td>
                                <td class="whitespace-nowrap">{{ $q->topik }}</td>
                                <td class="max-w-[420px]"><span class="line-clamp-2">{{ $q->isi }}</span></td>
                                <td class="whitespace-nowrap">{{ \App\Support\Format::shortDate($q->created_at) }}<small>{{ $q->created_at->format('H.i') }} WITA</small></td>
                                <td><span @class(['pnl-tag', 'pnl-tag--new' => $q->status === \App\Enums\QuestionStatus::Baru, 'pnl-tag--on' => $q->status === \App\Enums\QuestionStatus::Dijawab])>{{ $q->status->label() }}</span></td>
                                <td><div class="pnl-actions"><x-dk.button variant="secondary" size="sm" wire:click="lihat({{ $q->id }})">Buka</x-dk.button></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pnl-pager">{{ $questions->links() }}</div>
        @endif
    </div>

    @if ($detail)
        @php($dijawab = $detail->status === \App\Enums\QuestionStatus::Dijawab)
        <x-ad.modal title="Pertanyaan dari {{ $detail->nama }}">
            <div class="grid gap-5">
                <dl class="pnl-kv">
                    <dt>{{ $detail->isEmail() ? 'Email' : 'WhatsApp' }}</dt><dd>{{ $detail->kontak }}</dd>
                    <dt>Topik</dt><dd>{{ $detail->topik }}</dd>
                    <dt>Masuk</dt><dd>{{ \App\Support\Format::longDate($detail->created_at) }}, {{ $detail->created_at->format('H.i') }} WITA</dd>
                    @if ($detail->answered_at)<dt>Dijawab</dt><dd>{{ \App\Support\Format::longDate($detail->answered_at) }}, {{ $detail->answered_at->format('H.i') }} WITA</dd>@endif
                </dl>
                <blockquote class="pnl-quote">{{ $detail->isi }}</blockquote>

                @if ($templateList)
                    <div class="pnl-field">
                        <span class="pnl-label" id="tpl-label">Template pesan</span>
                        <div class="dk-chips" role="group" aria-labelledby="tpl-label">
                            @foreach ($templateList as $i => $t)
                                <button type="button" @class(['dk-chip', 'is-active' => $template === $i]) aria-pressed="{{ $template === $i ? 'true' : 'false' }}" wire:click="pakaiTemplate({{ $i }})" wire:key="tpl-{{ $i }}">
                                    <x-dk.icon name="message-square-text" :size="15" /> {{ $t['judul'] }}
                                </button>
                            @endforeach
                        </div>
                        <span class="pnl-help">Memilih template mengganti isi pesan di bawah.</span>
                    </div>
                @endif

                <x-ad.field label="Pesan WhatsApp" for="pesan" error="pesan"
                    help="{{ $detail->whatsappLink() ? 'Pesan ini terbuka di WhatsApp, tinggal Anda kirim. Pesan juga disimpan sebagai catatan jawaban.' : 'Penanya ini meninggalkan email, bukan nomor WhatsApp. Pesan disimpan sebagai catatan jawaban.' }}">
                    <textarea id="pesan" class="pnl-textarea" rows="10" wire:model="pesan" maxlength="2000"></textarea>
                </x-ad.field>
            </div>
            <x-slot:footer>
                <x-dk.button variant="ghost" icon="trash-2" class="mr-auto !text-danger" wire:click="hapus({{ $detail->id }})" wire:confirm="Hapus pertanyaan ini?">Hapus</x-dk.button>
                @if ($dijawab)
                    <x-dk.button variant="secondary" wire:click="tandai({{ $detail->id }}, false)">Tandai belum dijawab</x-dk.button>
                @endif
                @if ($detail->whatsappLink())
                    {{-- The tab opens on the click itself so pop-up blockers allow it; it gets the chat once the question is marked answered. --}}
                    <x-dk.button variant="primary" icon="whatsapp" ::disabled="! ($wire.pesan || '').trim()"
                        @click="const tab = window.open('', '_blank'); $wire.kirim({{ $detail->id }}).then((url) => { if (! url) { tab?.close(); return; } if (tab) { tab.opener = null; tab.location.href = url; } else { window.location.href = url; } })">
                        {{ $dijawab ? 'Kirim ulang lewat WhatsApp' : 'Kirim lewat WhatsApp' }}
                    </x-dk.button>
                @else
                    <x-dk.button variant="secondary" icon="mail" :href="'mailto:'.$detail->kontak.'?subject='.rawurlencode('Jawaban pertanyaan: '.$detail->topik).'&body='.rawurlencode($pesan)">Buka email</x-dk.button>
                    @unless ($dijawab)
                        <x-dk.button variant="primary" icon="circle-check" wire:click="tandai({{ $detail->id }}, true)">Tandai sudah dijawab</x-dk.button>
                    @endunless
                @endif
            </x-slot:footer>
        </x-ad.modal>
    @endif

    @if ($kelolaTemplate)
        <x-ad.modal title="Template balasan" size="wide" close="tutupTemplate">
            <div class="grid gap-5">
                <p class="pnl-help !text-[14px]">
                    Template mengisi pesan WhatsApp saat Anda membalas pertanyaan. Tulis kata dalam kurung kurawal untuk diisi otomatis:
                    @foreach ($placeholders as $kode => $arti)
                        <code>{{ $kode }}</code> {{ $arti }}{{ $loop->last ? '.' : ',' }}
                    @endforeach
                </p>
                @foreach ($templates as $i => $t)
                    <div class="grid gap-3 rounded-md border border-line p-4" wire:key="tpl-edit-{{ $i }}">
                        <div class="flex items-end gap-3">
                            <x-ad.field label="Judul" for="tpl-judul-{{ $i }}" error="templates.{{ $i }}.judul" class="flex-1">
                                <input id="tpl-judul-{{ $i }}" class="pnl-input" wire:model="templates.{{ $i }}.judul" maxlength="40" placeholder="Contoh: Jawaban umum">
                            </x-ad.field>
                            <button type="button" class="dk-iconbtn" wire:click="hapusTemplate({{ $i }})" aria-label="Hapus template {{ $t['judul'] }}"><x-dk.icon name="trash-2" :size="18" /></button>
                        </div>
                        <x-ad.field label="Isi pesan" for="tpl-isi-{{ $i }}" error="templates.{{ $i }}.isi">
                            <textarea id="tpl-isi-{{ $i }}" class="pnl-textarea" rows="7" wire:model="templates.{{ $i }}.isi" maxlength="1500"></textarea>
                        </x-ad.field>
                    </div>
                @endforeach
                @if (count($templates) < $maksTemplate)
                    <div><x-dk.button variant="secondary" icon="plus" wire:click="tambahTemplate">Tambah template</x-dk.button></div>
                @endif
            </div>
            <x-slot:footer>
                <x-dk.button variant="ghost" wire:click="tutupTemplate">Batal</x-dk.button>
                <x-dk.button variant="primary" icon="save" wire:click="simpanTemplate">Simpan template</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
