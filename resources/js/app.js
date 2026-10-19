import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import a11yMenu, { restoreSaved as restoreA11y } from './dispusip/a11y';
import clock from './dispusip/clock';
import countUp from './dispusip/count-up';
import gallery from './dispusip/gallery';
import guestbookDone from './dispusip/guestbook-done';
import heroSlider from './dispusip/hero';
import lockCountdown from './dispusip/lock-countdown';
import qrCode from './dispusip/qr';
import selfieCamera from './dispusip/camera';
import visitCode from './dispusip/visit-code';
import visitorChart from './dispusip/chart';
import visitorStats from './dispusip/stats';
import { formatNumber } from './dispusip/format';
import { startReveal } from './dispusip/reveal';
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
    Alpine.data('clock', clock);
    Alpine.data('countUp', countUp);
    Alpine.data('gallery', gallery);
    Alpine.data('guestbookDone', guestbookDone);
    Alpine.data('heroSlider', heroSlider);
    Alpine.data('lockCountdown', lockCountdown);
    Alpine.data('qrCode', qrCode);
    Alpine.data('selfieCamera', selfieCamera);
    Alpine.data('visitCode', visitCode);
    Alpine.data('visitorChart', visitorChart);
    Alpine.data('visitorStats', visitorStats);
    Alpine.magic('fmt', () => formatNumber);

    startReveal();
    startTableLabels();
    Livewire.start();
}
