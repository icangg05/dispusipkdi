import { onMotionChange, prefersReducedMotion } from './motion';

/**
 * Scroll animations in the manner of AOS: an element with data-aos="fade-up" (or fade, fade-left, fade-right,
 * zoom-in) eases in the first time it scrolls into view, and data-aos-delay="120" (ms) staggers neighbours.
 *
 * Unlike the AOS library, an element waits behind a held Web Animation instead of a class. Livewire re-renders
 * reset classes and styles to the server's HTML, which makes AOS content vanish after every filter or search;
 * an animation is not an attribute, so it survives. Without JavaScript, or with reduced motion, all content is
 * simply visible.
 */
const FROM = {
    fade: { opacity: 0 },
    'fade-up': { opacity: 0, transform: 'translateY(28px)' },
    'fade-left': { opacity: 0, transform: 'translateX(28px)' },
    'fade-right': { opacity: 0, transform: 'translateX(-28px)' },
    'zoom-in': { opacity: 0, transform: 'scale(.94)' },
};
const TO = { opacity: 1, transform: 'none' };
const DURATION = 700;
const EASING = 'cubic-bezier(.2, .7, .2, 1)';

/** An element animates once its top passes this share of the viewport height. */
const LINE = 0.92;

/** @type {Map<Element, Animation>} elements still waiting, each with the animation that holds it hidden */
const waiting = new Map();
const seen = new WeakSet();
let queued = false;

const fromFrame = (el) => FROM[el.dataset.aos] ?? FROM['fade-up'];

function release(el) {
    waiting.get(el)?.cancel();
    waiting.delete(el);
}

function show(el) {
    if (!waiting.has(el)) return;
    const from = fromFrame(el);
    const to = Object.fromEntries(Object.keys(from).map((key) => [key, TO[key]]));
    // Start the entrance (it keeps the first frame during its delay) before dropping the hold: no flash between.
    el.animate([from, to], { duration: DURATION, delay: Number(el.dataset.aosDelay) || 0, easing: EASING, fill: 'backwards' });
    release(el);
}

function watch(el) {
    if (seen.has(el)) return;
    seen.add(el);
    if (prefersReducedMotion() || typeof el.animate !== 'function') return;
    const from = fromFrame(el);
    waiting.set(el, el.animate([from, from], { duration: 1, fill: 'forwards' }));
}

/**
 * Positions are checked on scroll rather than with an IntersectionObserver: a fast jump (End key, dragging the
 * scrollbar, an anchor) can carry an element from below the screen to above it without it ever intersecting.
 */
function check() {
    queued = false;
    const line = window.innerHeight * LINE;

    [...waiting.keys()].forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.bottom < 0) release(el);
        else if (rect.top < line) show(el);
    });
}

function queueCheck() {
    if (queued || waiting.size === 0) return;
    queued = true;
    requestAnimationFrame(check);
}

function scan(node) {
    if (node.nodeType !== Node.ELEMENT_NODE) return;
    if (node.matches('[data-aos]')) watch(node);
    node.querySelectorAll('[data-aos]').forEach(watch);
}

function showAll() {
    [...waiting.keys()].forEach(release);
}

export function startReveal() {
    scan(document.body);
    queueCheck();

    // New content from Livewire, Alpine (x-for) and wire:navigate page swaps.
    new MutationObserver((records) => {
        records.forEach((record) => record.addedNodes.forEach(scan));
        if (records.some((record) => record.removedNodes.length)) {
            [...waiting.keys()].filter((el) => !el.isConnected).forEach(release);
        }
        queueCheck();
    }).observe(document.documentElement, { childList: true, subtree: true });

    window.addEventListener('scroll', queueCheck, { passive: true });
    window.addEventListener('resize', queueCheck, { passive: true });

    onMotionChange(() => prefersReducedMotion() && showAll());
    window.addEventListener('beforeprint', showAll);
}
