const number = new Intl.NumberFormat('id-ID');

export const formatNumber = (n) => number.format(Math.round(Number(n) || 0));

/** Signed percentage with at most one decimal: "+8,9%" or "−3%". */
export const formatDelta = (pct) =>
    (pct >= 0 ? '+' : '−') + Math.abs(pct).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';

/** Axis label: 1500 → "1,5 rb". */
export const formatCompact = (n) =>
    n >= 1000 ? (n / 1000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' rb' : String(n);

export const escapeHtml = (s) =>
    String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
