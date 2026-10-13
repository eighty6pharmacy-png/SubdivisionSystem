/**
 * Client-side table pagination (15 rows default). Rows must be marked with .pagination-match after filtering.
 */
window.initTablePagination = function initTablePagination(config) {
    const rowSelector = config.rowSelector;
    const matchClass = config.matchClass || 'pagination-match';
    const perPage = config.perPage || 15;
    const infoEl = config.infoEl ? document.querySelector(config.infoEl) : null;
    const prevBtn = config.prevEl ? document.querySelector(config.prevEl) : null;
    const nextBtn = config.nextEl ? document.querySelector(config.nextEl) : null;
    let page = 1;

    function matchedRows() {
        return Array.from(document.querySelectorAll(rowSelector)).filter((row) =>
            row.classList.contains(matchClass)
        );
    }

    function render() {
        const rows = matchedRows();
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage) || 1);
        if (page > totalPages) {
            page = totalPages;
        }
        const start = (page - 1) * perPage;
        const end = start + perPage;

        document.querySelectorAll(rowSelector).forEach((row) => {
            if (!row.classList.contains(matchClass)) {
                row.style.display = 'none';
                return;
            }
            const index = rows.indexOf(row);
            row.style.display = index >= start && index < end ? '' : 'none';
        });

        if (infoEl) {
            if (total === 0) {
                infoEl.textContent = 'No rows to display';
            } else {
                infoEl.textContent = `Showing ${start + 1}–${Math.min(end, total)} of ${total}`;
            }
        }
        if (prevBtn) {
            prevBtn.disabled = page <= 1;
            prevBtn.style.opacity = page <= 1 ? '0.45' : '1';
        }
        if (nextBtn) {
            nextBtn.disabled = page >= totalPages;
            nextBtn.style.opacity = page >= totalPages ? '0.45' : '1';
        }
    }

    prevBtn?.addEventListener('click', () => {
        if (page > 1) {
            page--;
            render();
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (page * perPage < matchedRows().length) {
            page++;
            render();
        }
    });

    render();

    return {
        resetPage() {
            page = 1;
            render();
        },
        render,
    };
};
