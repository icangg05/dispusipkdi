/** Kiosk clock in WITA, refreshed every 15 seconds. */
export default () => ({
    time: '',

    init() {
        const fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
        const tick = () => (this.time = fmt.format(new Date()).replace(':', '.'));
        tick();
        this.interval = setInterval(tick, 15000);
    },

    destroy() {
        clearInterval(this.interval);
    },
});
