/**
 * Advanced Client-side Table Pagination and Sorting Utility for Admin Panels
 * Provides dynamic page size selection (10, 15, 25, 50, 100), numbered pagination pills,
 * and column sorting for clean database-style data grids.
 */
(function() {
    window.initTablePagination = function initTablePagination(config) {
        const rowSelector = config.rowSelector;
        const matchClass = config.matchClass || 'pagination-match';
        const infoEl = config.infoEl ? document.querySelector(config.infoEl) : null;
        const prevBtn = config.prevEl ? document.querySelector(config.prevEl) : null;
        const nextBtn = config.nextEl ? document.querySelector(config.nextEl) : null;

        // Auto-detect page size select element if not explicitly provided
        let pageSizeEl = config.pageSizeEl ? document.querySelector(config.pageSizeEl) : null;
        if (!pageSizeEl && config.infoEl) {
            const derivedPageSizeId = config.infoEl.replace('PageInfo', 'PageSize');
            pageSizeEl = document.querySelector(derivedPageSizeId);
        }

        // Auto-detect page numbers container element if not explicitly provided
        let numbersEl = config.numbersEl ? document.querySelector(config.numbersEl) : null;
        if (!numbersEl && config.infoEl) {
            const derivedNumbersId = config.infoEl.replace('PageInfo', 'PageNumbers');
            numbersEl = document.querySelector(derivedNumbersId);
        }

        let perPage = pageSizeEl ? parseInt(pageSizeEl.value, 10) || (config.perPage || 15) : (config.perPage || 15);
        let page = 1;

        if (pageSizeEl) {
            pageSizeEl.addEventListener('change', () => {
                perPage = parseInt(pageSizeEl.value, 10) || 15;
                page = 1;
                render();
            });
        }

        function matchedRows() {
            return Array.from(document.querySelectorAll(rowSelector)).filter((row) =>
                row.classList.contains(matchClass)
            );
        }

        function renderPageNumbers(totalPages) {
            if (!numbersEl) return;
            numbersEl.innerHTML = '';
            if (totalPages <= 1) return;

            const maxVisibleButtons = 5;
            let startPage = 1;
            let endPage = totalPages;

            if (totalPages > maxVisibleButtons) {
                const half = Math.floor(maxVisibleButtons / 2);
                startPage = Math.max(1, page - half);
                endPage = startPage + maxVisibleButtons - 1;
                if (endPage > totalPages) {
                    endPage = totalPages;
                    startPage = Math.max(1, endPage - maxVisibleButtons + 1);
                }
            }

            if (startPage > 1) {
                appendPageBtn(1);
                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '…';
                    dots.style.cssText = 'padding: 4px 6px; font-size: 12px; color: #94a3b8; font-weight: 700;';
                    numbersEl.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                appendPageBtn(i);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.textContent = '…';
                    dots.style.cssText = 'padding: 4px 6px; font-size: 12px; color: #94a3b8; font-weight: 700;';
                    numbersEl.appendChild(dots);
                }
                appendPageBtn(totalPages);
            }
        }

        function appendPageBtn(pNum) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = pNum;
            const isCurrent = pNum === page;
            btn.style.cssText = `
                min-width: 32px;
                height: 32px;
                padding: 0 8px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.15s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid ${isCurrent ? '#0284c7' : '#cbd5e1'};
                background: ${isCurrent ? '#0284c7' : '#ffffff'};
                color: ${isCurrent ? '#ffffff' : '#475569'};
            `;
            if (!isCurrent) {
                btn.onmouseover = () => { btn.style.background = '#f1f5f9'; btn.style.borderColor = '#94a3b8'; };
                btn.onmouseout = () => { btn.style.background = '#ffffff'; btn.style.borderColor = '#cbd5e1'; };
            }
            btn.addEventListener('click', () => {
                page = pNum;
                render();
            });
            numbersEl.appendChild(btn);
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
                    infoEl.textContent = 'No matching rows found';
                } else {
                    infoEl.textContent = `Showing ${start + 1}–${Math.min(end, total)} of ${total}`;
                }
            }

            if (prevBtn) {
                prevBtn.disabled = page <= 1;
                prevBtn.style.opacity = page <= 1 ? '0.45' : '1';
                prevBtn.style.cursor = page <= 1 ? 'not-allowed' : 'pointer';
            }
            if (nextBtn) {
                nextBtn.disabled = page >= totalPages;
                nextBtn.style.opacity = page >= totalPages ? '0.45' : '1';
                nextBtn.style.cursor = page >= totalPages ? 'not-allowed' : 'pointer';
            }

            renderPageNumbers(totalPages);
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

        // Setup Column Sorting if table exists
        setupTableSorting();

        function setupTableSorting() {
            const sampleRow = document.querySelector(rowSelector);
            const table = sampleRow ? sampleRow.closest('table') : (config.table ? document.querySelector(config.table) : null);
            if (!table) return;

            const headers = table.querySelectorAll('thead th');
            headers.forEach((th, colIdx) => {
                const sortType = th.getAttribute('data-sort') || (th.classList.contains('sortable') ? 'auto' : null);
                if (!sortType && !th.hasAttribute('data-sortable')) return;

                th.style.cursor = 'pointer';
                th.style.userSelect = 'none';
                th.setAttribute('title', 'Click to sort');

                // Add sort indicator if missing
                let indicator = th.querySelector('.sort-indicator');
                if (!indicator) {
                    indicator = document.createElement('span');
                    indicator.className = 'sort-indicator';
                    indicator.style.cssText = 'margin-left: 6px; font-size: 11px; opacity: 0.4; transition: opacity 0.2s;';
                    indicator.textContent = '⇅';
                    th.appendChild(indicator);
                }

                th.addEventListener('click', () => {
                    const currentDir = th.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
                    headers.forEach(h => {
                        h.removeAttribute('data-sort-dir');
                        const ind = h.querySelector('.sort-indicator');
                        if (ind) {
                            ind.textContent = '⇅';
                            ind.style.opacity = '0.35';
                        }
                    });

                    th.setAttribute('data-sort-dir', currentDir);
                    indicator.textContent = currentDir === 'asc' ? '▲' : '▼';
                    indicator.style.opacity = '1';

                    sortTable(table, colIdx, currentDir, sortType);
                    page = 1;
                    render();
                });
            });
        }

        function sortTable(table, colIdx, direction, type) {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            const allRows = Array.from(tbody.querySelectorAll(rowSelector));

            allRows.sort((rowA, rowB) => {
                const cellA = rowA.children[colIdx];
                const cellB = rowB.children[colIdx];
                if (!cellA || !cellB) return 0;

                let valA = cellA.getAttribute('data-sort-val') || cellA.innerText.trim();
                let valB = cellB.getAttribute('data-sort-val') || cellB.innerText.trim();

                // Clean numbers & currency (e.g. ₱1,250.00 -> 1250.00)
                const numA = parseFloat(valA.replace(/[^0-9.-]+/g, ''));
                const numB = parseFloat(valB.replace(/[^0-9.-]+/g, ''));
                const isNumeric = !isNaN(numA) && !isNaN(numB) && (type === 'number' || (!isNaN(Number(valA.replace(/₱|,/g, ''))) && valA !== ''));

                if (isNumeric) {
                    return direction === 'asc' ? numA - numB : numB - numA;
                }

                // Check dates
                const dateA = Date.parse(valA);
                const dateB = Date.parse(valB);
                if (type === 'date' || (!isNaN(dateA) && !isNaN(dateB) && valA.length > 5 && isNaN(valA))) {
                    return direction === 'asc' ? dateA - dateB : dateB - dateA;
                }

                // String comparison
                const comp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
                return direction === 'asc' ? comp : -comp;
            });

            allRows.forEach(row => tbody.appendChild(row));
        }

        render();

        return {
            resetPage() {
                page = 1;
                render();
            },
            render,
            setPerPage(newPerPage) {
                perPage = newPerPage;
                if (pageSizeEl) pageSizeEl.value = newPerPage;
                page = 1;
                render();
            }
        };
    };
})();
