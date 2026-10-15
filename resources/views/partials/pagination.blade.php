@if ($paginator->hasPages())
    <nav class="dk-pager" aria-label="Halaman">
        <span class="dk-pager__info">Menampilkan {{ $paginator->firstItem() }} sampai {{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>
        <ul class="dk-pager__list" role="list">
            <li>
                <button type="button" class="dk-pager__btn" wire:click="previousPage('{{ $paginator->getPageName() }}')" @disabled($paginator->onFirstPage()) aria-label="Halaman sebelumnya">
                    <x-dk.icon name="chevron-left" :size="18" />
                </button>
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="dk-pager__gap">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li wire:key="page-{{ $page }}">
                            <button type="button" @class(['dk-pager__btn', 'is-active' => $page == $paginator->currentPage()])
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</button>
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                <button type="button" class="dk-pager__btn" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages()) aria-label="Halaman berikutnya">
                    <x-dk.icon name="chevron-right" :size="18" />
                </button>
            </li>
        </ul>
    </nav>
@endif
