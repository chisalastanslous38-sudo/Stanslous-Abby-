/**
 * Product Search Pro – Frontend Logic
 * Premium version with live search, filters, skeleton loading
 */

document.addEventListener('DOMContentLoaded', function () {
    const input      = document.getElementById('psp-search-input');
    const clearBtn   = document.getElementById('psp-clear-btn');
    const results    = document.getElementById('psp-results');
    const filterBox  = document.getElementById('psp-filters');

    if (!input || !results) return;

    const products = (typeof PSPData !== 'undefined' && PSPData.products) ? PSPData.products : [];
    if (filterBox) {
        const categories = [...new Set(products.map(p => (p.category || '').trim()).filter(Boolean))].sort((a,b)=>a.localeCompare(b));
        filterBox.innerHTML = '<button class=\"psp-filter-chip active\" data-category=\"all\">All</button>' + categories.map(c => `<button class=\"psp-filter-chip\" data-category=\"${escapeHtml(c)}\">${escapeHtml(c)}</button>`).join('');
    }
    let activeCategory = 'all';
    let debounceTimer = null;

    // ---------- Helpers ----------
    function escapeHtml(str) {
        if (typeof str !== 'string') return str;
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getStatusLabel(status) {
        return {
            in_stock: 'In Stock',
            low_stock: 'Low Stock',
            out_of_stock: 'Out of Stock'
        }[status] || status;
    }

    function showToast(message) {
        let toast = document.querySelector('.psp-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'psp-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2200);
    }

    function starSvg() {
        return `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
    }

    // ---------- Render Functions ----------
    function renderSkeleton() {
        results.innerHTML = `
            <div class="psp-skeleton-grid">
                ${[1,2,3].map(() => `
                    <div class="psp-skeleton-card">
                        <div class="psp-skeleton-img"></div>
                        <div class="psp-skeleton-body">
                            <div class="psp-skeleton-line short"></div>
                            <div class="psp-skeleton-line"></div>
                            <div class="psp-skeleton-line medium"></div>
                        </div>
                    </div>
                `).join('')}
            </div>`;
    }

    function renderInitial() {
        results.innerHTML = `
            <div class="psp-initial">
                <div class="psp-initial-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <h3>Start searching</h3>
                <p>Enter a product name or an 8-digit ID to find what you need</p>
            </div>`;
    }

    function renderEmpty(query) {
        results.innerHTML = `
            <div class="psp-empty">
                <div class="psp-empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <h3>No products found</h3>
                <p>We couldn’t find anything matching “${escapeHtml(query)}”. Try a different name or ID.</p>
            </div>`;
    }

    function renderCards(items) {
        if (items.length === 0) return;

        // Use list layout when few results, grid when many
        if (items.length <= 3) {
            results.innerHTML = `<div class="psp-list">
                ${items.map(p => `
                    <div class="psp-list-item" data-id="${escapeHtml(p.id)}">
                        <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy">
                        <div class="psp-list-info">
                            <div class="psp-list-name">${escapeHtml(p.name)}</div>
                            <div class="psp-list-meta">ID: ${escapeHtml(p.id)} · ${escapeHtml(p.category)} · $${Number(p.price).toFixed(2)}</div>
                            <span class="psp-status-badge ${escapeHtml(p.status)}" style="margin-top:6px;display:inline-flex;">
                                ${getStatusLabel(p.status)}
                            </span>
                        </div>
                    </div>
                `).join('')}
            </div>`;
        } else {
            results.innerHTML = `<div class="psp-grid">
                ${items.map(p => `
                    <div class="psp-card" data-id="${escapeHtml(p.id)}">
                        <div class="psp-card-image">
                            <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy">
                            <span class="psp-card-badge ${escapeHtml(p.status)}">${getStatusLabel(p.status)}</span>
                        </div>
                        <div class="psp-card-body">
                            <div class="psp-card-category">${escapeHtml(p.category)}</div>
                            <h3 class="psp-card-name">${escapeHtml(p.name)}</h3>
                            <div class="psp-card-meta">ID: ${escapeHtml(p.id)}</div>
                            <div class="psp-card-footer">
                                <div class="psp-card-price">$${Number(p.price).toFixed(2)}</div>
                                <div class="psp-card-rating">
                                    ${starSvg()} ${p.rating} <span style="color:#94a3b8">(${p.reviews})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>`;
        }

        // Click handlers
        results.querySelectorAll('[data-id]').forEach(el => {
            el.addEventListener('click', () => {
                const id = el.getAttribute('data-id');
                const product = products.find(p => p.id === id);
                if (product) renderDetail(product);
            });
        });
    }

    function renderDetail(p) {
        results.innerHTML = `
            <div class="psp-detail">
                <button class="psp-detail-back" id="psp-back">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to results
                </button>

                <div class="psp-detail-hero">
                    <div class="psp-detail-image">
                        <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}">
                    </div>
                    <div class="psp-detail-info">
                        <h2>${escapeHtml(p.name)}</h2>

                        <div class="psp-detail-badges">
                            <span class="psp-status-badge ${escapeHtml(p.status)}">${getStatusLabel(p.status)}</span>
                            <span class="psp-category-badge">${escapeHtml(p.category)}</span>
                        </div>

                        <div class="psp-detail-price">$${Number(p.price).toFixed(2)}</div>

                        <div class="psp-detail-rating">
                            ${starSvg()} ${p.rating} · ${p.reviews} reviews
                        </div>

                        <p class="psp-detail-description">${escapeHtml(p.description)}</p>

                        <div class="psp-detail-specs">
                            <div class="psp-spec-row">
                                <span class="psp-spec-label">Product ID</span>
                                <span class="psp-spec-value">
                                    ${escapeHtml(p.id)}
                                    <button class="psp-copy-btn" data-copy="${escapeHtml(p.id)}">Copy</button>
                                </span>
                            </div>
                            <div class="psp-spec-row">
                                <span class="psp-spec-label">SKU</span>
                                <span class="psp-spec-value">${escapeHtml(p.sku)}</span>
                            </div>
                            <div class="psp-spec-row">
                                <span class="psp-spec-label">Category</span>
                                <span class="psp-spec-value">${escapeHtml(p.category)}</span>
                            </div>
                            <div class="psp-spec-row">
                                <span class="psp-spec-label">Stock</span>
                                <span class="psp-spec-value">${p.stock} units</span>
                            </div>
                            <div class="psp-spec-row">
                                <span class="psp-spec-label">Status</span>
                                <span class="psp-spec-value">${getStatusLabel(p.status)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        document.getElementById('psp-back').addEventListener('click', () => {
            doSearch(true); // re-run last search without skeleton
        });

        results.querySelectorAll('.psp-copy-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const text = btn.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Product ID copied');
                }).catch(() => {
                    showToast('Could not copy');
                });
            });
        });
    }

    // ---------- Search Logic ----------
    function doSearch(skipSkeleton = false) {
        const query = input.value.trim();

        // Show / hide clear button
        clearBtn.style.display = query ? 'flex' : 'none';

        if (!query) {
            renderInitial();
            return;
        }

        if (!skipSkeleton) renderSkeleton();

        // Small delay for skeleton effect
        setTimeout(() => {
            const isIdSearch = /^\d{8}$/.test(query);
            let matches = [];

            if (isIdSearch) {
                matches = products.filter(p => p.id === query);
            } else {
                const lower = query.toLowerCase();
                matches = products.filter(p =>
                    p.name.toLowerCase().includes(lower) ||
                    p.category.toLowerCase().includes(lower) ||
                    (p.sku && p.sku.toLowerCase().includes(lower))
                );
            }

            // Apply category filter
            if (activeCategory !== 'all') {
                matches = matches.filter(p => p.category === activeCategory);
            }

            if (matches.length === 0) {
                renderEmpty(query);
            } else if (matches.length === 1 && isIdSearch) {
                renderDetail(matches[0]);
            } else {
                renderCards(matches);
            }
        }, skipSkeleton ? 0 : 320);
    }

    // ---------- Event Listeners ----------
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => doSearch(), 280);
    });

    input.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            clearTimeout(debounceTimer);
            doSearch();
        }
    });

    clearBtn.addEventListener('click', () => {
        input.value = '';
        clearBtn.style.display = 'none';
        activeCategory = 'all';
        document.querySelectorAll('.psp-filter-chip').forEach(c => {
            c.classList.toggle('active', c.dataset.category === 'all');
        });
        renderInitial();
        input.focus();
    });

    // Category filters
    if (filterBox) {
        filterBox.addEventListener('click', (e) => {
            const chip = e.target.closest('.psp-filter-chip');
            if (!chip) return;

            document.querySelectorAll('.psp-filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeCategory = chip.dataset.category;

            if (input.value.trim()) {
                doSearch();
            }
        });
    }

    // Initial state
    renderInitial();
});
