/**
 * News article: the cover (marked data-zoom) and every picture in the body open enlarged in a lightbox.
 * Arrow keys or a swipe step through them, Esc closes, and clicking a picture larger than the
 * screen shows it at its real size (scroll to look around, click again to fit it back).
 * Pictures that are themselves links keep working as links.
 */
export default () => ({
    images: [],
    open: -1,
    full: false,
    trigger: null,
    touchX: null,

    init() {
        this.images = [...this.$el.querySelectorAll('img[data-zoom], .dk-prose img')].filter((img) => !img.closest('a'));

        this.images.forEach((img, k) => {
            img.classList.add('dk-zoomable');
            img.tabIndex = 0;
            img.setAttribute('role', 'button');
            img.setAttribute('aria-label', 'Perbesar gambar' + (img.alt ? ': ' + img.alt : ''));
            img.addEventListener('click', () => this.show(k));
            img.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.show(k);
                }
            });
        });
    },

    get cur() {
        const img = this.images[this.open];

        return img ? { src: img.currentSrc || img.src, alt: img.alt } : null;
    },

    show(k) {
        this.trigger = document.activeElement;
        this.open = k;
        this.full = false;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.close?.focus());
    },

    destroy() {
        if (this.open >= 0) document.documentElement.style.overflow = '';
    },

    close() {
        this.open = -1;
        this.full = false;
        document.documentElement.style.overflow = '';
        this.trigger?.focus?.();
    },

    step(d) {
        const n = this.images.length;

        if (n > 1) {
            this.full = false;
            this.open = (this.open + d + n) % n;
        }
    },

    toggleFull(e) {
        const img = e.currentTarget;

        if (!this.full && img.naturalWidth <= img.clientWidth + 1 && img.naturalHeight <= img.clientHeight + 1) return;

        const x = e.offsetX / img.clientWidth;
        const y = e.offsetY / img.clientHeight;
        this.full = !this.full;

        // Keep the clicked spot under the pointer once the picture is shown at full size.
        this.$nextTick(() => {
            const box = img.closest('.dk-zoom');
            if (!this.full || !box) return;
            box.scrollLeft = x * img.clientWidth - box.clientWidth / 2;
            box.scrollTop = y * img.clientHeight - box.clientHeight / 2;
        });
    },

    swipeStart(e) {
        this.touchX = this.full || e.touches.length > 1 ? null : e.touches[0].clientX;
    },

    swipeEnd(e) {
        if (this.touchX === null) return;
        const dx = e.changedTouches[0].clientX - this.touchX;
        this.touchX = null;
        if (Math.abs(dx) > 50) this.step(dx < 0 ? 1 : -1);
    },

    key(e) {
        if (this.open < 0) return;
        if (e.key === 'Escape') this.close();
        if (e.key === 'ArrowRight') this.step(1);
        if (e.key === 'ArrowLeft') this.step(-1);
    },
});
