<?php

use App\Models\Service;
use App\Support\Media;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Profil Dinas')] class extends Component
{
    public function with(): array
    {
        $profil = site()->profil();

        return [
            'profil' => $profil,
            'struktur' => Media::url($profil['struktur'] ?? null),
            'services' => Service::query()->active()->get(),
            'sejarah' => array_filter(preg_split('/\R{2,}/', trim((string) ($profil['sejarah'] ?? '')))),
        ];
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Profil']]" title="Profil Dinas"
        :text="'Visi, misi, tugas, dan layanan '.site()->get('identitas.nama').' untuk warga Kota Kendari.'" />

    <x-dk.section tight>
        <x-dk.profile-block heading-id="tentang-dispusip" />
    </x-dk.section>

    @if (count($sejarah))
        <x-dk.section tone="sunken">
            <div class="dk-twocol">
                <x-dk.section-heading eyebrow="Sejarah singkat" eyebrow-icon="history" title="Perjalanan" accent="kami" align="left" />
                <div class="dk-prose">
                    @foreach ($sejarah as $paragraf)
                        <p>{{ $paragraf }}</p>
                    @endforeach
                </div>
            </div>
        </x-dk.section>
    @endif

    <x-dk.section>
        <div class="dk-twocol">
            <div>
                <x-dk.section-heading eyebrow="Arah kerja" eyebrow-icon="target" title="Visi &" accent="Misi" align="left" />
                <blockquote class="dk-visi"><x-dk.icon name="quote" :size="28" /><p>{{ $profil['visi'] }}</p></blockquote>
                <ol class="dk-misi">
                    @foreach ($profil['misi'] as $misi)
                        <li><span>{{ $loop->iteration }}</span>{{ $misi }}</li>
                    @endforeach
                </ol>
            </div>
            <div>
                <x-dk.section-heading eyebrow="Kewenangan" eyebrow-icon="landmark" title="Tugas &" accent="Fungsi" align="left" />
                <ul class="dk-points" role="list">
                    @foreach ($profil['tugas'] as $tugas)
                        <li><span class="dk-tile dk-tile--sm"><x-dk.icon :name="$tugas['icon'] ?: 'book-open'" :size="18" /></span><span><strong>{{ $tugas['title'] }}</strong><small>{{ $tugas['text'] }}</small></span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-dk.section>

    @if ($struktur)
        <x-dk.section tone="sunken">
            <x-dk.section-heading eyebrow="Organisasi" eyebrow-icon="users" title="Struktur" accent="Organisasi" align="center" />
            <figure class="dk-struktur">
                <img src="{{ $struktur }}" alt="Bagan struktur organisasi {{ site()->get('identitas.nama') }}" loading="lazy">
                <figcaption>Struktur organisasi {{ site()->get('identitas.nama') }}.</figcaption>
            </figure>
        </x-dk.section>
    @endif

    @if ($services->isNotEmpty())
        <x-dk.section id="layanan" :tone="$struktur ? null : 'sunken'">
            <x-dk.section-heading eyebrow="Apa yang bisa kami bantu?" eyebrow-icon="layers" title="Layanan" accent="Kami" align="center"
                description="Pilih layanan yang Anda butuhkan. Semuanya gratis untuk warga Kota Kendari." />
            <div class="dk-svcgrid">
                @foreach ($services as $service)
                    <x-dk.service-card :service="$service" wire:key="svc-{{ $service->id }}" />
                @endforeach
            </div>
        </x-dk.section>
    @endif
</div>
