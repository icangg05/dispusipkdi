/**
 * On phones the admin tables turn into stacked cards (admin.css), each cell headed by its column name
 * from data-label. The labels are copied from the table head here, so the Blade tables stay plain, and
 * are put back whenever Livewire re-renders a table (a morph resets attributes to the server's HTML).
 * A head cell holding only screen-reader text (the "Aksi" column) gives no visible label.
 */
let queued = false;

function headLabel(th) {
    const text = th.textContent.trim();
    const hidden = th.querySelector('.dk-sr');

    return hidden && hidden.textContent.trim() === text ? '' : text;
}

function label(table) {
    const heads = [...table.querySelectorAll('thead th')].map(headLabel);

    table.querySelectorAll('tbody tr').forEach((row) => {
        let column = 0;

        [...row.children].forEach((cell) => {
            const span = cell.colSpan || 1;
            const text = span > 1 ? '' : (heads[column] ?? '');
            if (cell.getAttribute('data-label') !== text) cell.setAttribute('data-label', text);
            column += span;
        });
    });
}

function run() {
    queued = false;
    document.querySelectorAll('.ad-table').forEach(label);
}

function queue() {
    if (queued) return;
    queued = true;
    requestAnimationFrame(run);
}

export function startTableLabels() {
    run();
    new MutationObserver(queue).observe(document.documentElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['data-label'],
    });
}
