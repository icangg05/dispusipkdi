<?php

use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\Service;
use App\Models\Slide;
use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public function with(): array
    {
        $slides = Slide::query()->active()->get();

        if ($slides->isEmpty()) {
            $slides = collect([new Slide([
                'image' => '/images/contoh/ruang-baca.jpg',
                'caption' => 'Ruang Baca Umum',
                'kicker' => 'Selamat datang di Dispusip Kota Kendari',
                'title' => 'Ruang baca untuk',
                'accent' => 'seluruh warga Kendari',
                'text' => 'Pinjam buku, baca di tempat, dan telusuri arsip daerah. Gratis, nyaman, dan dekat dengan Anda.',
            ])]);
        }

        return [
            'slides' => $slides,
            'services' => Service::query()->active()->get(),
            'gallery' => MediaItem::query()->published()->limit(9)->get()->map->toGalleryItem()->all(),
            'faqs' => Faq::query()->active()->get(),
            'stats' => app(StatistikService::class)->all(),
            'youtube' => collect(site()->sosmed())->firstWhere('platform', 'youtube'),
        ];
    }
};
?>

<div>
    <div class="dk-page__hero">
        <x-dk.navbar overlay />
        <x-dk.hero-slider :slides="$slides" :tamu-hari-ini="$stats['tamu']['hariIni']" />
    </div>

    <x-dk.quick-stats />

    <x-dk.section id="tentang">
        <x-dk.profile-block />
    </x-dk.section>

    @if ($services->isNotEmpty())
        <x-dk.section id="layanan" tone="sunken">
            <x-dk.section-heading eyebrow="Apa yang bisa kami bantu?" eyebrow-icon="layers" title="Layanan" accent="Kami" align="center"
                description="Pilih layanan yang Anda butuhkan. Semuanya gratis untuk warga Kota Kendari." />
            <div class="dk-svcgrid">
                @foreach ($services as $service)
                    <x-dk.service-card :service="$service" wire:key="svc-{{ $service->id }}" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 80 }}" />
                @endforeach
            </div>
        </x-dk.section>
    @endif

    <x-dk.section id="berita-sec">
        <livewire:news-section />
    </x-dk.section>

    <x-dk.section id="galeri-sec" tone="sunken">
        <x-dk.section-heading eyebrow="Galeri" eyebrow-icon="image" title="Dokumentasi" accent="& Galeri" id="galeri"
            description="Momen kegiatan, fasilitas, dan video layanan Dispusip Kota Kendari.">
            @if ($youtube)
                <x-slot:action>
                    <x-dk.button variant="ghost" size="sm" icon="youtube" :href="$youtube['href']" target="_blank" rel="noopener">Kanal YouTube</x-dk.button>
                </x-slot:action>
            @endif
        </x-dk.section-heading>
        <x-dk.media-gallery :items="$gallery">
            <div class="dk-center"><x-dk.button variant="secondary" icon-right="arrow-right" :href="route('galeri')" wire:navigate>Lihat semua galeri</x-dk.button></div>
        </x-dk.media-gallery>
        @if (count(site()->sosmed()))
            <div class="dk-follow" data-aos="fade-up">
                <div class="dk-follow__text">
                    <strong>Ikuti kabar terbaru Dispusip</strong>
                    <span>Info layanan, jadwal perpustakaan keliling, dan dokumentasi kegiatan di media sosial kami.</span>
                </div>
                <x-dk.social-links variant="cards" />
            </div>
        @endif
    </x-dk.section>

    <x-dk.visitor-stats :stats="$stats" />

    <x-dk.section id="tanya">
        <x-dk.section-heading eyebrow="Tanya pustakawan" eyebrow-icon="message-circle" title="Ada" accent="pertanyaan?"
            description="Cari dulu di daftar pertanyaan yang sering diajukan. Jika belum terjawab, kirim pertanyaan Anda langsung kepada pustakawan kami." />
        <div class="dk-ask">
            <x-dk.faq-list :items="$faqs" data-aos="fade-up" />
            <div data-aos="fade-up" data-aos-delay="100"><livewire:question-form /></div>
        </div>
    </x-dk.section>

    <x-dk.section id="kontak" tone="sunken">
        <x-dk.section-heading eyebrow="Kontak" eyebrow-icon="phone" title="Hubungi" accent="Kami" align="center"
            description="Datang langsung ke kantor kami, atau hubungi lewat WhatsApp dan email pada jam layanan." />
        <x-dk.contact-info />
    </x-dk.section>
</div>
