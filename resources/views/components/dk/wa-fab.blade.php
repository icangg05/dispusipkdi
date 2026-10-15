@php($kontak = site()->kontak())
<div class="dk-fab">
    <a class="dk-fab__top" href="#top" aria-label="Kembali ke atas"><x-dk.icon name="arrow-up" :size="18" /></a>
    @if ($kontak['whatsappLink'])
        <a class="dk-fab__wa" href="{{ $kontak['whatsappLink'] }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp" title="Chat WhatsApp">
            <x-dk.icon name="whatsapp" :size="26" />
        </a>
    @endif
</div>
