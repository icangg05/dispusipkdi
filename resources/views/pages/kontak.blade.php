<?php

use App\Models\Faq;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Kontak')] class extends Component
{
    public function with(): array
    {
        return ['faqs' => Faq::query()->active()->get()];
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Kontak']]" title="Hubungi Kami"
        text="Datang langsung ke kantor kami, atau hubungi lewat WhatsApp dan email pada jam layanan.">
        <x-slot:aside>
            <div class="dk-hariini">
                <span class="dk-hariini__date">{{ \App\Support\Format::longDate() }}</span>
                <x-dk.status variant="dot" />
            </div>
        </x-slot:aside>
    </x-dk.page-top>

    <x-dk.section tight>
        <x-dk.contact-info />
    </x-dk.section>

    @if (count(site()->sosmed()))
        <x-dk.section tone="sunken" tight>
            <div class="dk-follow" style="margin-top: 0">
                <div class="dk-follow__text">
                    <strong>Ikuti kabar terbaru Dispusip</strong>
                    <span>Info layanan, jadwal perpustakaan keliling, dan dokumentasi kegiatan di media sosial kami.</span>
                </div>
                <x-dk.social-links variant="cards" />
            </div>
        </x-dk.section>
    @endif

    <x-dk.section id="tanya">
        <x-dk.section-heading eyebrow="Tanya pustakawan" eyebrow-icon="message-circle" title="Ada" accent="pertanyaan?"
            description="Cari dulu di daftar pertanyaan yang sering diajukan. Jika belum terjawab, kirim pertanyaan Anda langsung kepada pustakawan kami." />
        <div class="dk-ask">
            <x-dk.faq-list :items="$faqs" data-aos="fade-up" />
            <div data-aos="fade-up" data-aos-delay="100"><livewire:question-form /></div>
        </div>
    </x-dk.section>
</div>
