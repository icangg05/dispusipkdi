import { animationsStopped, onMotionChange, prefersReducedMotion } from './motion';

/**
 * Hero background slider. Each slide's progress bar is its timer: the slide advances when the
 * active bar's CSS animation ends, so pausing the bar pauses the slider (WCAG 2.2.2).
 */
export default ({ count = 1, interval = 6000 } = {}) => ({
    i: 0,
    count,
    interval,
    userPaused: false,
    userPlay: false,
    reduce: false,
    timer: null,

    init() {
        this.reduce = prefersReducedMotion();
        this.cleanup = onMotionChange(() => {
            this.reduce = prefersReducedMotion();
            this.schedule();
        });
        this.schedule();
    },

    destroy() {
        this.cleanup?.();
        clearTimeout(this.timer);
    },

    get paused() {
        return this.userPaused || (this.reduce && !this.userPlay);
    },

    toggle() {
        const pause = !this.paused;
        this.userPaused = pause;
        this.userPlay = !pause;
        this.schedule();
    },

    go(k) {
        this.i = ((k % this.count) + this.count) % this.count;
        this.schedule();
    },

    next() {
        this.go(this.i + 1);
    },

    prev() {
        this.go(this.i - 1);
    },

    ended(k) {
        if (k === this.i && !this.paused && this.count > 1) this.next();
    },

    // With "Hentikan animasi" the bars never animate; if the visitor still presses play, use a timer.
    schedule() {
        clearTimeout(this.timer);
        if (this.paused || this.count < 2 || !animationsStopped()) return;
        this.timer = setTimeout(() => this.next(), this.interval);
    },
});
