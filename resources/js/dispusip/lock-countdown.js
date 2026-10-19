/**
 * Counts down a sign-in lock, then asks the server to render the form again, so it unlocks
 * without a page reload. The server stays the judge: if a second is still left, it sends a
 * fresh countdown.
 */
export default ({ seconds }) => ({
    left: seconds,
    timer: null,

    init() {
        this.timer = setInterval(() => {
            if (this.left > 1) {
                this.left--;
                return;
            }
            clearInterval(this.timer);
            this.left = 0;
            setTimeout(() => this.$wire.$refresh(), 500);
        }, 1000);
    },

    destroy() {
        clearInterval(this.timer);
    },
});
