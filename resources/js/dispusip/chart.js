import { escapeHtml, formatCompact, formatNumber } from './format';

const niceMax = (v) => {
    if (v <= 0) return 10;
    const p = Math.pow(10, Math.floor(Math.log10(v)));
    const m = v / p;
    const s = m <= 1 ? 1 : m <= 2 ? 2 : m <= 2.5 ? 2.5 : m <= 5 ? 5 : 10;
    return s * p;
};

/**
 * Single-series column chart (design system VisitorChart): current period in lentera,
 * earlier periods in muted teal, value label on the current bar, tooltip on hover.
 *
 * Options are getters so a parent component can swap the series reactively:
 * visitorChart({ data: () => points, title: () => 'Tamu · 7 hari terakhir', unit: () => 'tamu' })
 */
export default ({ data, title = () => '', unit = () => 'kunjungan', height = 220, dark = true } = {}) => ({
    width: 640,
    hover: -1,
    height,
    dark,

    get points() {
        return (typeof data === 'function' ? data() : data) || [];
    },
    get titleText() {
        return typeof title === 'function' ? title() : title;
    },
    get unitText() {
        return typeof unit === 'function' ? unit() : unit;
    },

    init() {
        const measure = () => {
            const w = Math.round(this.$refs.plot.clientWidth);
            if (w > 0) this.width = w;
        };
        measure();
        if (window.ResizeObserver) {
            this.ro = new ResizeObserver(measure);
            this.ro.observe(this.$refs.plot);
        }
    },

    destroy() {
        this.ro?.disconnect();
    },

    geometry() {
        const W = Math.max(260, this.width);
        const H = this.height;
        const padL = 40, padR = 4, padT = 28, padB = 30;
        const pts = this.points;
        const max = niceMax(Math.max(0, ...pts.map((d) => d.value)) * 1.08);
        const iw = W - padL - padR;
        const ih = H - padT - padB;
        const slot = iw / Math.max(1, pts.length);
        const bw = Math.min(24, slot * 0.56);
        const y = (v) => padT + ih - (v / max) * ih;
        return { W, H, padL, padR, padT, padB, max, iw, ih, slot, bw, y };
    },

    svg() {
        const g = this.geometry();
        const pts = this.points;
        const ticks = [0, 0.25, 0.5, 0.75, 1].map((t) => Math.round(g.max * t));
        let out = '';

        for (const t of ticks) {
            out += `<line class="dk-chart__grid" x1="${g.padL}" x2="${g.W - g.padR}" y1="${g.y(t)}" y2="${g.y(t)}"/>`;
            out += `<text class="dk-chart__tick" x="${g.padL - 8}" y="${g.y(t) + 4}" text-anchor="end">${formatCompact(t)}</text>`;
        }

        pts.forEach((d, k) => {
            const x = g.padL + g.slot * k + (g.slot - g.bw) / 2;
            const h = Math.max(0, (d.value / g.max) * g.ih);
            const top = g.padT + g.ih - h;
            const r = Math.min(4, h);
            const cur = d.current ? ' is-current' : '';

            out += `<g data-k="${k}">`;
            out += `<rect class="dk-chart__hit" x="${g.padL + g.slot * k}" y="${g.padT}" width="${g.slot}" height="${g.ih + g.padB}"/>`;
            if (this.hover === k) {
                out += `<rect class="dk-chart__band" x="${g.padL + g.slot * k + 2}" y="${g.padT}" width="${Math.max(0, g.slot - 4)}" height="${g.ih}" rx="3"/>`;
            }
            if (h > 0) {
                const base = g.padT + g.ih;
                out += `<path class="dk-chart__bar${cur}" d="M${x},${base} V${top + r} Q${x},${top} ${x + r},${top} H${x + g.bw - r} Q${x + g.bw},${top} ${x + g.bw},${top + r} V${base} Z"/>`;
                if (d.current) {
                    out += `<text class="dk-chart__val" x="${x + g.bw / 2}" y="${top - 8}" text-anchor="middle">${formatNumber(d.value)}</text>`;
                }
            } else {
                out += `<text class="dk-chart__note" x="${x + g.bw / 2}" y="${g.padT + g.ih - 6}" text-anchor="middle">${escapeHtml(d.note || '0')}</text>`;
            }
            out += `<text class="dk-chart__x${cur}" x="${x + g.bw / 2}" y="${g.H - 8}" text-anchor="middle">${escapeHtml(d.label)}</text>`;
            out += '</g>';
        });

        out += `<line class="dk-chart__base" x1="${g.padL}" x2="${g.W - g.padR}" y1="${g.padT + g.ih}" y2="${g.padT + g.ih}"/>`;

        const label = (this.titleText || 'Grafik') + ': ' + pts.map((d) => d.label + ' ' + formatNumber(d.value)).join(', ');

        return `<svg viewBox="0 0 ${g.W} ${g.H}" width="${g.W}" height="${g.H}" role="img" aria-label="${escapeHtml(label)}">${out}</svg>`;
    },

    over(event) {
        const el = event.target.closest('[data-k]');
        this.hover = el ? Number(el.dataset.k) : -1;
    },

    get tip() {
        const d = this.points[this.hover];
        if (!d) return null;
        const g = this.geometry();
        return {
            left: ((g.padL + g.slot * this.hover + g.slot / 2) / g.W) * 100 + '%',
            top: (g.y(d.value) / g.H) * 100 + '%',
            value: formatNumber(d.value),
            sub: d.label + (d.note ? ' · ' + d.note : ''),
        };
    },
});
