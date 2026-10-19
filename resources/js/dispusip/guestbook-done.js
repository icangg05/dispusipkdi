import { prefersReducedMotion } from './motion';

/**
 * The guestbook sheet once it is recorded. On a phone the long form collapses into this short
 * card and leaves the visitor far below it, so the card scrolls itself into view. On the
 * front-desk tablet it also clears itself for the next guest.
 */
export default ({ resetAfter = 0 } = {}) => ({
    timer: null,

    init() {
        requestAnimationFrame(() => {
            const top = this.$el.getBoundingClientRect().top;
            if (top < 0 || top > window.innerHeight * 0.5) {
                this.$el.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
            }
        });

        if (resetAfter) {
            this.timer = setTimeout(() => this.$wire.ulang(), resetAfter);
        }
    },

    destroy() {
        clearTimeout(this.timer);
    },
});
