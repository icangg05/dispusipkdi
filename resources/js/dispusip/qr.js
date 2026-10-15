/** Draws a QR code (SVG) for a URL. The encoder is loaded only on pages that use it. */
export default (url) => ({
    async init() {
        const { default: QR } = await import('qrcode');
        this.$el.innerHTML = await QR.toString(url, { type: 'svg', margin: 1, errorCorrectionLevel: 'M', color: { dark: '#06292e', light: '#ffffff' } });
    },
});
