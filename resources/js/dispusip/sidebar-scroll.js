/**
 * wire:navigate swaps in the next admin page with its sidebar scrolled back to the top. The offset is
 * read just before the swap and put back in the same frame, so the menu stays where it was, like an SPA.
 * The sidebar itself is still replaced, so the active link and the new-question count stay current.
 */
export function startSidebarScroll() {
    document.addEventListener('livewire:navigating', (e) => {
        const top = document.querySelector('.pnl-side')?.scrollTop ?? 0;
        if (!top) return;

        e.detail.onSwap(() => {
            const side = document.querySelector('.pnl-side');
            if (side) side.scrollTop = top;
        });
    });
}
