import { formatNumber } from './format';
import { prefersReducedMotion } from './motion';

/** A number that counts up once when it scrolls into view. */
export default (target = 0, duration = 1100) => ({
    value: Number(target) || 0,
    target: Number(target) || 0,

    init() {
        if (prefersReducedMotion() || !this.target) return;

        const run = () => {
            let start;
            this.value = 0;
            const step = (t) => {
                start ??= t;
                const p = Math.min(1, (t - start) / duration);
                this.value = Math.round(this.target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };

        if (!('IntersectionObserver' in window)) return run();

        this.io = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting)) {
                this.io.disconnect();
                run();
            }
        });
        this.io.observe(this.$el);
    },

    destroy() {
        this.io?.disconnect();
    },

    get text() {
        return formatNumber(this.value);
    },
});
