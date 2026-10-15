/**
 * Accessibility menu (bottom-left): text size, high contrast, greyscale, link highlight,
 * readable font, text spacing, stop animations, big cursor and read-aloud (id-ID).
 * Effects are dk-a11y-* classes and data-theme="kontras" on <html>; choices are kept per device.
 */
const AWAL = { teks: 0, kontras: false, abu: false, tautan: false, font: false, spasi: false, diam: false, kursor: false, suara: false };
const FLAGS = ['abu', 'tautan', 'font', 'spasi', 'diam', 'kursor', 'suara'];
export const SKALA = [100, 115, 130];
const STORAGE_KEY = 'dispusip-a11y';

function read(key) {
    if (!key) return { ...AWAL };
    try {
        const v = JSON.parse(window.localStorage.getItem(key) || 'null');
        return v ? { ...AWAL, ...v } : { ...AWAL };
    } catch {
        return { ...AWAL };
    }
}

function applyTo(el, st) {
    el.classList.toggle('dk-a11y-teks-1', st.teks === 1);
    el.classList.toggle('dk-a11y-teks-2', st.teks === 2);
    FLAGS.forEach((k) => el.classList.toggle('dk-a11y-' + k, !!st[k]));
    if (st.kontras) el.setAttribute('data-theme', 'kontras');
    else el.removeAttribute('data-theme');
}

/**
 * wire:navigate copies the new page's <html> attributes over the current ones, which drops these
 * classes; put the saved choices back in the same frame so the page never flashes without them.
 */
export function restoreSaved() {
    applyTo(document.documentElement, read(STORAGE_KEY));
}

export default ({ storageKey = STORAGE_KEY } = {}) => ({
    open: false,
    st: read(storageKey),
    tts: 'speechSynthesis' in window && typeof window.SpeechSynthesisUtterance === 'function',
    skala: SKALA,

    init() {
        this.apply();
        this.$watch('st', () => {
            this.apply();
            this.save();
        });
        this.speak = this.speak.bind(this);
        this.toggleSpeech();
    },

    destroy() {
        document.removeEventListener('click', this.speak, true);
    },

    get aktif() {
        return FLAGS.filter((k) => this.st[k]).length + (this.st.kontras ? 1 : 0) + (this.st.teks ? 1 : 0);
    },

    apply() {
        applyTo(document.documentElement, this.st);
    },

    save() {
        if (!storageKey) return;
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(this.st));
        } catch {
            /* storage unavailable */
        }
    },

    toggle(k) {
        if (k === 'suara' && !this.tts) return;
        this.st = { ...this.st, [k]: !this.st[k] };
        if (k === 'suara') this.toggleSpeech();
    },

    size(d) {
        this.st = { ...this.st, teks: Math.min(2, Math.max(0, this.st.teks + d)) };
    },

    reset() {
        this.st = { ...AWAL };
        this.toggleSpeech();
    },

    show() {
        this.open = true;
        this.$nextTick(() => this.$refs.panel?.querySelector('button:not([disabled])')?.focus());
    },

    hide() {
        this.open = false;
        this.$refs.fab?.focus();
    },

    toggleSpeech() {
        document.removeEventListener('click', this.speak, true);
        if (this.st.suara && this.tts) document.addEventListener('click', this.speak, true);
        else if (this.tts) window.speechSynthesis.cancel();
    },

    speak(e) {
        if (e.target.closest?.('.dk-a11y')) return;
        const el = e.target.closest?.('h1,h2,h3,h4,p,li,td,th,label,figcaption,blockquote,a,button,strong,small,span');
        if (!el) return;
        const txt = (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 600);
        if (!txt) return;
        window.speechSynthesis.cancel();
        const u = new window.SpeechSynthesisUtterance(txt);
        u.lang = 'id-ID';
        window.speechSynthesis.speak(u);
    },
});
