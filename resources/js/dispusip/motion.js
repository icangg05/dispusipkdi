/** True when the visitor asked for less motion (system setting or "Hentikan animasi"). */
export const prefersReducedMotion = () =>
    (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) ||
    document.documentElement.classList.contains('dk-a11y-diam');

export const animationsStopped = () => document.documentElement.classList.contains('dk-a11y-diam');

/** Calls back whenever either motion preference changes; returns a cleanup function. */
export function onMotionChange(callback) {
    const media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    media?.addEventListener?.('change', callback);
    const observer = window.MutationObserver ? new MutationObserver(callback) : null;
    observer?.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    return () => {
        media?.removeEventListener?.('change', callback);
        observer?.disconnect();
    };
}

/** Replays a CSS entrance animation on an element. */
export function replayAnimation(el) {
    if (!el) return;
    el.style.animation = 'none';
    void el.offsetWidth;
    el.style.animation = '';
}
