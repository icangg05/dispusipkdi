/**
 * Four boxes for the daily visit code. Digits flow forward as you type, Backspace walks back,
 * pasting "1234" fills all four. The joined value goes to a hidden input bound with wire:model.
 */
export default () => ({
    digits: ['', '', '', ''],

    init() {
        const start = String(this.$refs.value?.value || '').replace(/\D/g, '').slice(0, 4);
        start.split('').forEach((d, i) => (this.digits[i] = d));
    },

    box(i) {
        return this.$refs['d' + i];
    },

    input(i, e) {
        const v = e.target.value.replace(/\D/g, '');
        if (v.length > 1) {
            this.fill(v, i);
            return;
        }
        this.digits[i] = v;
        e.target.value = v;
        if (v && i < 3) this.box(i + 1).focus();
        this.sync();
    },

    keydown(i, e) {
        if (e.key === 'Backspace' && !this.digits[i] && i > 0) {
            e.preventDefault();
            this.digits[i - 1] = '';
            this.box(i - 1).focus();
            this.sync();
        } else if (e.key === 'ArrowLeft' && i > 0) {
            this.box(i - 1).focus();
        } else if (e.key === 'ArrowRight' && i < 3) {
            this.box(i + 1).focus();
        }
    },

    paste(e) {
        const v = (e.clipboardData?.getData('text') || '').replace(/\D/g, '');
        if (!v) return;
        e.preventDefault();
        this.fill(v, 0);
    },

    fill(v, from) {
        v.slice(0, 4 - from).split('').forEach((d, k) => (this.digits[from + k] = d));
        this.box(Math.min(3, from + v.length)).focus();
        this.sync();
    },

    clear() {
        this.digits = ['', '', '', ''];
        this.sync();
        this.$nextTick(() => this.box(0)?.focus());
    },

    sync() {
        const el = this.$refs.value;
        el.value = this.digits.join('');
        el.dispatchEvent(new Event('input', { bubbles: true }));
    },
});
