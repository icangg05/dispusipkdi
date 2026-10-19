<?php

use App\Models\Category;
use App\Models\Post;
use App\Support\Media;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    public string $title = '';

    public string $slug = '';

    public bool $slugManual = false;

    public ?int $category_id = null;

    public string $excerpt = '';

    public string $body = '';

    public string $author = '';

    public bool $is_published = true;

    public string $published_at = '';

    public $gambar = null;

    public bool $hapusGambar = false;

    public bool $pratinjau = false;

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->post = $post;
            $this->fill($post->only(['title', 'slug', 'category_id', 'excerpt', 'body', 'author', 'is_published']));
            $this->excerpt = (string) $post->excerpt;
            $this->body = (string) $post->body;
            $this->author = (string) $post->author;
            $this->published_at = ($post->published_at ?? now())->format('Y-m-d\TH:i');
            $this->slugManual = true;
        } else {
            $this->category_id = Category::query()->ordered()->value('id');
            $this->author = auth()->user()->name;
            $this->published_at = now()->format('Y-m-d\TH:i');
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->slugManual) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManual = true;
        $this->slug = Str::slug($this->slug);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash'],
            'category_id' => ['required', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'author' => ['nullable', 'string', 'max:80'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'gambar' => ['nullable', 'image', 'max:4096'],
        ], [
            'title.required' => 'Tulis judul berita.',
            'category_id.required' => 'Pilih kategori.',
            'body.required' => 'Tulis isi berita.',
            'slug.alpha_dash' => 'Alamat hanya boleh berisi huruf, angka, dan tanda hubung.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar paling besar 4 MB.',
        ]);

        $post = $this->post ?? new Post(['user_id' => auth()->id()]);
        $image = $post->image;

        if ($this->hapusGambar) {
            Media::delete($image);
            $image = null;
        }

        if ($this->gambar) {
            $image = Media::store($this->gambar, 'berita', $image);
        }

        $post->fill([
            'title' => $data['title'],
            'slug' => Post::uniqueSlug($data['slug'] ?: $data['title'], $post->id),
            'category_id' => $data['category_id'],
            'excerpt' => $data['excerpt'] ?: null,
            'body' => $data['body'],
            'author' => $data['author'] ?: null,
            'is_published' => $data['is_published'],
            'published_at' => $data['published_at'] ? Carbon::parse($data['published_at']) : ($data['is_published'] ? now() : null),
            'image' => $image,
        ])->save();

        session()->flash('status', $this->post ? 'Perubahan berita disimpan.' : 'Berita baru disimpan.');

        $this->redirectRoute('admin.berita.index', navigate: true);
    }

    public function render()
    {
        return $this->view([
            'categories' => Category::query()->ordered()->get(),
            'html' => $this->pratinjau ? Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '',
        ])->title($this->post ? 'Ubah berita' : 'Tulis berita');
    }
};
?>

<form class="pnl-grid" style="gap: var(--space-6)" wire:submit="simpan">
    <div class="pnl-pagehead">
        <div>
            <h1>{{ $post ? 'Ubah berita' : 'Tulis berita' }}</h1>
            <p>Judul dan isi ditulis dengan huruf kalimat. Isi mendukung format Markdown sederhana.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="ghost" icon="arrow-left" :href="route('admin.berita.index')" wire:navigate>Kembali</x-dk.button>
            @if ($post?->isVisible())
                <x-dk.button variant="secondary" icon="external-link" :href="route('berita.show', $post)" target="_blank">Lihat di situs</x-dk.button>
            @endif
            <x-dk.button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="simpan, gambar">Simpan</x-dk.button>
        </div>
    </div>

    <div class="pnl-grid pnl-grid--main">
        <div class="pnl-card">
            <div class="pnl-card__body pnl-form">
                <x-ad.field label="Judul" for="title" error="title">
                    <input id="title" class="pnl-input text-[18px] font-bold" wire:model.blur="title" maxlength="200" placeholder="Contoh: Jadwal layanan selama libur akhir tahun">
                </x-ad.field>
                <x-ad.field label="Alamat halaman" for="slug" error="slug" help="{{ url('/berita') }}/<strong>{{ $slug ?: 'judul-berita' }}</strong>">
                    <input id="slug" class="pnl-input pnl-input--sm font-mono" wire:model.blur="slug" maxlength="220">
                </x-ad.field>
                <x-ad.field label="Ringkasan" for="excerpt" error="excerpt" optional help="Satu atau dua kalimat yang tampil di kartu berita dan sebagai pembuka artikel.">
                    <textarea id="excerpt" class="pnl-textarea" rows="3" maxlength="300" wire:model="excerpt"></textarea>
                </x-ad.field>
                <div class="pnl-field {{ $errors->has('body') ? 'has-error' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <label class="pnl-label" for="body">Isi berita</label>
                        <div class="dk-tabs !mb-0" role="tablist">
                            <button type="button" role="tab" @class(['dk-tab', 'is-active' => ! $pratinjau]) wire:click="$set('pratinjau', false)">Tulis</button>
                            <button type="button" role="tab" @class(['dk-tab', 'is-active' => $pratinjau]) wire:click="$set('pratinjau', true)">Pratinjau</button>
                        </div>
                    </div>
                    @if ($pratinjau)
                        <div class="dk-prose min-h-[340px] rounded-sm border border-line bg-surface p-5">{!! $html ?: '<p class="text-ink-muted">Belum ada isi.</p>' !!}</div>
                    @else
                        <textarea id="body" class="pnl-textarea pnl-textarea--tall" wire:model="body" placeholder="Tulis isi berita di sini…"></textarea>
                    @endif
                    <span class="pnl-help">Format: <code>## Subjudul</code>, <code>**tebal**</code>, <code>*miring*</code>, <code>- daftar</code>, <code>1. daftar bernomor</code>, <code>[teks](https://tautan)</code>, <code>&gt; kutipan</code>. Pisahkan paragraf dengan baris kosong.</span>
                    @error('body')<span class="pnl-error"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="pnl-grid" style="align-content: start">
            <div class="pnl-card">
                <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="send" :size="18" /> Terbitkan</h2></div>
                <div class="pnl-card__body pnl-form">
                    <x-ad.check model="is_published" live>Tampilkan di situs</x-ad.check>
                    <x-ad.field label="Tanggal terbit" for="published_at" error="published_at" help="Tanggal di masa depan membuat berita terjadwal.">
                        <input id="published_at" type="datetime-local" class="pnl-input" wire:model="published_at">
                    </x-ad.field>
                    <x-ad.field label="Penulis" for="author" error="author" optional>
                        <input id="author" class="pnl-input" wire:model="author" maxlength="80" placeholder="Contoh: Bidang Layanan">
                    </x-ad.field>
                </div>
            </div>
            <div class="pnl-card">
                <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="tag" :size="18" /> Kategori</h2></div>
                <div class="pnl-card__body">
                    <x-ad.field error="category_id">
                        <div class="grid gap-2">
                            @foreach ($categories as $category)
                                <label class="pnl-check" wire:key="cat-{{ $category->id }}">
                                    <input type="radio" name="category_id" value="{{ $category->id }}" wire:model="category_id">
                                    <span class="dk-tick__box" aria-hidden="true"><x-dk.icon name="check" :size="16" :stroke="3" /></span>
                                    <x-dk.badge :category="$category" />
                                </label>
                            @endforeach
                        </div>
                    </x-ad.field>
                    <a class="pnl-help mt-3 inline-block underline" href="{{ route('admin.kategori') }}" wire:navigate>Kelola kategori</a>
                </div>
            </div>
            <div class="pnl-card">
                <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="image" :size="18" /> Gambar utama</h2></div>
                <div class="pnl-card__body pnl-form">
                    <x-ad.field error="gambar" help="Rasio 16:9 terlihat paling baik, misalnya 1600×900.">
                        <x-ad.image-upload model="gambar" :file="$gambar" :current="$hapusGambar ? null : $post?->image_url">
                            @if ($post?->image && ! $gambar)
                                <x-ad.check model="hapusGambar" live>Hapus gambar ini</x-ad.check>
                            @endif
                        </x-ad.image-upload>
                    </x-ad.field>
                </div>
            </div>
        </div>
    </div>
</form>
