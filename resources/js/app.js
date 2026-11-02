import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import a11yMenu, { restoreSaved as restoreA11y } from './dispusip/a11y';
import articleZoom from './dispusip/article-zoom';
import clock from './dispusip/clock';
import countUp from './dispusip/count-up';
import gallery from './dispusip/gallery';
import guestbookDone from './dispusip/guestbook-done';
import heroSlider from './dispusip/hero';
import lockCountdown from './dispusip/lock-countdown';
import navbar from './dispusip/navbar';
import qrCode from './dispusip/qr';
import selfieCamera from './dispusip/camera';
import visitCode from './dispusip/visit-code';
import visitorChart from './dispusip/chart';
import visitorStats from './dispusip/stats';
import youtubePreview from './dispusip/youtube-preview';
import { formatNumber } from './dispusip/format';
import { startReveal } from './dispusip/reveal';
import { startSidebarScroll } from './dispusip/sidebar-scroll';
import { startTableLabels } from './dispusip/table-labels';

/*
 * Pages switch through wire:navigate. A visit made after a deploy can pull in a newly hashed
 * bundle; reload once instead of running a second copy of Livewire next to the first.
 */
if (window.dispusipStarted) {
    window.location.reload();
} else {
    window.dispusipStarted = true;
    start();
}

function start() {
    document.addEventListener('livewire:navigating', (e) => e.detail.onSwap(restoreA11y));

    Alpine.data('a11yMenu', a11yMenu);
    Alpine.data('articleZoom', articleZoom);
    Alpine.data('clock', clock);
    Alpine.data('countUp', countUp);
    Alpine.data('gallery', gallery);
    Alpine.data('guestbookDone', guestbookDone);
    Alpine.data('heroSlider', heroSlider);
    Alpine.data('lockCountdown', lockCountdown);
    Alpine.data('navbar', navbar);
    Alpine.data('qrCode', qrCode);
    Alpine.data('selfieCamera', selfieCamera);
    Alpine.data('visitCode', visitCode);
    Alpine.data('visitorChart', visitorChart);
    Alpine.data('visitorStats', visitorStats);
    Alpine.data('youtubePreview', youtubePreview);
    Alpine.magic('fmt', () => formatNumber);

    startReveal();
    startSidebarScroll();
    startTableLabels();
    Livewire.start();
}
