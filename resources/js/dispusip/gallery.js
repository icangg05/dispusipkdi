/**
 * Photo & video gallery: Semua / Foto / Video tabs, mosaic grid (first tile double size)
 * and a lightbox driven by arrow keys and Esc. Videos play from YouTube inside the lightbox.
 */
export default (items = [], { tabs = true } = {}) => ({
    items,
    tab: 'semua',
    open: -1,
    trigger: null,

    get list() {
        return this.tab === 'semua' ? this.items : this.items.filter((x) => x.type === this.tab);
    },

    get cur() {
        return this.open >= 0 ? this.list[this.open] : null;
    },

    count(type) {
        return type === 'semua' ? this.items.length : this.items.filter((x) => x.type === type).length;
    },

    setTab(id) {
        this.tab = id;
        this.open = -1;
    },

    show(k) {
        this.trigger = document.activeElement;
        this.open = k;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.close?.focus());
    },

    destroy() {
        if (this.open >= 0) document.documentElement.style.overflow = '';
    },

    close() {
        this.open = -1;
        document.documentElement.style.overflow = '';
        this.trigger?.focus?.();
    },

    step(d) {
        const n = this.list.length;
        if (n) this.open = (this.open + d + n) % n;
    },

    key(e) {
        if (this.open < 0) return;
        if (e.key === 'Escape') this.close();
        if (e.key === 'ArrowRight') this.step(1);
        if (e.key === 'ArrowLeft') this.step(-1);
    },
});
