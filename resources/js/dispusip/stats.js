import { formatDelta, formatNumber } from './format';
import { replayAnimation } from './motion';

const pct = (a, b) => (b ? ((a - b) / b) * 100 : null);

/**
 * The Statistik band: switch the source (guestbook / website) and the period (daily / monthly / all).
 */
export default (stats) => ({
    stats,
    sumber: 'tamu',
    periode: 'harian',

    init() {
        this.$watch('sumber', () => replayAnimation(this.$refs.big));
        this.$watch('periode', () => replayAnimation(this.$refs.big));
    },

    get S() {
        return this.stats[this.sumber];
    },

    get ringkas() {
        const S = this.S;
        return {
            harian: { judul: 'Hari ini', nilai: S.hariIni, banding: pct(S.hariIni, S.kemarinSamaJam), bandingLbl: 'dibanding jam yang sama kemarin', seri: S.harian, judulSeri: '7 hari terakhir' },
            bulanan: { judul: 'Bulan ini', nilai: S.bulanIni, banding: pct(S.bulanIni, S.bulanLaluSamaPeriode), bandingLbl: 'dibanding periode sama bulan lalu', seri: S.bulanan, judulSeri: '12 bulan terakhir' },
            semua: { judul: 'Sepanjang waktu', nilai: S.total, banding: null, bandingLbl: 'sejak ' + S.sejak, seri: S.semua, judulSeri: 'Per tahun' },
        }[this.periode];
    },

    get chartTitle() {
        return this.S.label + ' · ' + this.ringkas.judulSeri;
    },

    get unit() {
        return this.sumber === 'tamu' ? 'tamu' : 'pengunjung';
    },

    fmt: formatNumber,
    delta: formatDelta,
});
