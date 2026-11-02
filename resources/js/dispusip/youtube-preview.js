/**
 * Plays a pasted YouTube link inside the gallery form through the IFrame Player API, so the
 * admin sees whether the video embeds, and fills the duration field with the length YouTube
 * reports. No API key needed; live streams report no length and leave the field as it is.
 */
let api = null;

function loadApi() {
    api ??= new Promise((resolve) => {
        if (window.YT?.Player) {
            resolve(window.YT);
            return;
        }
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            previous?.();
            resolve(window.YT);
        };
        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        document.head.append(script);
    });

    return api;
}

/** 205 → "3:25", 3723 → "1:02:03": the shapes the duration field accepts. */
function formatDuration(total) {
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = String(total % 60).padStart(2, '0');

    return h ? `${h}:${String(m).padStart(2, '0')}:${s}` : `${m}:${s}`;
}

export default (videoId) => ({
    player: null,

    async init() {
        const YT = await loadApi();
        this.player = new YT.Player(this.$refs.player, {
            host: 'https://www.youtube-nocookie.com',
            videoId,
            width: '100%',
            height: '100%',
            playerVars: { rel: 0 },
            events: { onReady: () => this.fillDuration() },
        });
    },

    // The length can read 0 for a moment after the player is ready, so look again a few times.
    fillDuration(tries = 0) {
        const seconds = Math.round(this.player?.getDuration?.() ?? 0);

        if (seconds > 0) {
            this.$wire.duration = formatDuration(seconds);
        } else if (tries < 5) {
            setTimeout(() => this.fillDuration(tries + 1), 600);
        }
    },

    destroy() {
        this.player?.destroy?.();
    },
});
