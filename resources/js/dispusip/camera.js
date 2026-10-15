/**
 * Selfie for the guest register. Opens the front camera, captures a 3:4 portrait, shrinks it,
 * and uploads it to the Livewire property given in `model`. Without camera access (no HTTPS,
 * permission denied, old browser) the visitor can pick or shoot a photo with the file input instead.
 */
const ERRORS = {
    NotAllowedError: 'Izin kamera ditolak. Izinkan kamera di pengaturan peramban, atau pakai tombol Unggah foto.',
    SecurityError: 'Izin kamera ditolak. Izinkan kamera di pengaturan peramban, atau pakai tombol Unggah foto.',
    NotFoundError: 'Kamera tidak ditemukan di perangkat ini. Pakai tombol Unggah foto.',
    OverconstrainedError: 'Kamera tidak ditemukan di perangkat ini. Pakai tombol Unggah foto.',
    NotReadableError: 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.',
};

export default ({ model = 'foto', maxWidth = 720, initial = null } = {}) => ({
    state: initial ? 'done' : 'idle', // idle | starting | live | uploading | done
    preview: initial,
    error: '',
    progress: 0,
    facing: 'user',
    canSwitch: false,
    stream: null,

    get supported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) && window.isSecureContext;
    },

    destroy() {
        this.stop();
    },

    async start() {
        this.error = '';

        if (!this.supported) {
            this.error = window.isSecureContext
                ? 'Peramban ini tidak dapat membuka kamera. Pakai tombol Unggah foto.'
                : 'Kamera hanya bisa dibuka lewat koneksi aman (HTTPS). Pakai tombol Unggah foto.';
            return;
        }

        this.stop();
        this.state = 'starting';

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: this.facing, width: { ideal: 1280 }, height: { ideal: 960 } },
                audio: false,
            });
            const video = this.$refs.video;
            video.srcObject = this.stream;
            await video.play();
            this.state = 'live';
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.canSwitch = devices.filter((d) => d.kind === 'videoinput').length > 1;
        } catch (e) {
            this.stop();
            this.state = this.preview ? 'done' : 'idle';
            this.error = ERRORS[e.name] || 'Kamera tidak dapat dibuka. Pakai tombol Unggah foto.';
        }
    },

    stop() {
        this.stream?.getTracks().forEach((t) => t.stop());
        this.stream = null;
        if (this.$refs.video) this.$refs.video.srcObject = null;
    },

    cancel() {
        this.stop();
        this.state = this.preview ? 'done' : 'idle';
    },

    async switchCamera() {
        this.facing = this.facing === 'user' ? 'environment' : 'user';
        await this.start();
    },

    capture() {
        const video = this.$refs.video;
        if (!video || !video.videoWidth) return;
        this.draw(video, video.videoWidth, video.videoHeight, this.facing === 'user');
        this.stop();
    },

    pick(event) {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            this.error = 'Pilih berkas gambar (JPG atau PNG).';
            return;
        }

        this.error = '';
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            this.draw(img, img.naturalWidth, img.naturalHeight, false);
            URL.revokeObjectURL(url);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            this.error = 'Gambar tidak dapat dibaca. Coba foto lain.';
        };
        img.src = url;
    },

    /** Crop the source to a centred 3:4 portrait, scale it down and upload it as JPEG. */
    draw(source, sw, sh, mirror) {
        let cw = sw;
        let ch = Math.round((sw * 4) / 3);
        if (ch > sh) {
            ch = sh;
            cw = Math.round((sh * 3) / 4);
        }
        const scale = Math.min(1, maxWidth / cw);
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(cw * scale);
        canvas.height = Math.round(ch * scale);
        const ctx = canvas.getContext('2d');
        if (mirror) {
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(source, (sw - cw) / 2, (sh - ch) / 2, cw, ch, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    this.error = 'Foto gagal diproses. Coba lagi.';
                    this.state = 'idle';
                    return;
                }
                this.setPreview(URL.createObjectURL(blob));
                this.upload(new File([blob], 'foto-tamu.jpg', { type: 'image/jpeg' }));
            },
            'image/jpeg',
            0.85,
        );
    },

    setPreview(url) {
        if (this.preview && this.preview.startsWith('blob:')) URL.revokeObjectURL(this.preview);
        this.preview = url;
    },

    upload(file) {
        this.state = 'uploading';
        this.progress = 0;
        this.$wire.upload(
            model,
            file,
            () => {
                this.state = 'done';
            },
            () => {
                this.state = 'idle';
                this.setPreview(null);
                this.error = 'Foto gagal diunggah. Periksa koneksi lalu coba lagi.';
            },
            (e) => {
                this.progress = e.detail.progress;
            },
        );
    },

    retake() {
        this.setPreview(null);
        this.state = 'idle';
        this.$wire.set(model, null, false);
        this.start();
    },

    reset() {
        this.stop();
        this.setPreview(null);
        this.error = '';
        this.state = 'idle';
    },
});
