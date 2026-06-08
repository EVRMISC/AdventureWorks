<?php
$pageTitle = 'Vendors';
$activePage = 'vendors';
require 'header.php';
?>

    <style>
        th.sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        th.sortable:hover {
            color: var(--text);
            background: #232b28;
        }

        th.sortable .sort-icon {
            display: inline-block;
            margin-left: 5px;
            font-size: 10px;
            color: var(--text-muted);
            transition: color 0.15s;
        }

        th.sortable.active-asc .sort-icon::after  { content: '▲'; color: var(--accent); }
        th.sortable.active-desc .sort-icon::after { content: '▼'; color: var(--accent); }
        th.sortable:not(.active-asc):not(.active-desc) .sort-icon::after { content: '⇅'; }

        .search-box:focus-within {
            border-color: var(--accent);
        }

        tbody tr {
            animation: fadeRow 0.15s ease;
        }

        @keyframes fadeRow {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> purchasing <span>/</span> vendors</div>
            <h2>Vendors</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count">Loading...</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search vendor name or account...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable" data-col="AccountNumber">Account<span class="sort-icon"></span></th>
                        <th class="sortable active-asc" data-col="Name">Vendor Name<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="CreditRating">Credit Rating<span class="sort-icon"></span></th>
                        <th>Preferred</th>
                        <th>Status</th>
                        <th class="sortable" data-col="OrderCount">Orders<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="TotalSpent">Total Spent<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="vendor-tbody">
                    <tr><td colspan="7" class="empty">Loading...</td></tr>
                </tbody>
            </table>

            <div id="pagination" class="pagination" style="display:none;">
                <span id="page-info"></span>
                <div id="page-buttons" style="display:flex;gap:4px;margin-left:auto;"></div>
            </div>
        </div>
    </div>

    <script>
        let currentPage   = 1;
        let currentSearch = '';
        let currentSort   = 'Name';
        let currentDir    = 'ASC';
        let debounceTimer = null;

        function fetchVendors(page, search, sort, dir) {
            currentPage   = page;
            currentSearch = search;
            currentSort   = sort;
            currentDir    = dir;

            const params = new URLSearchParams({ page, search, sort, dir });

            document.getElementById('vendor-tbody').style.opacity = '0.4';

            fetch('vendors_data.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('vendor-tbody').style.opacity = '1';
                    renderTable(data);
                    renderPagination(data);
                    updateSortHeaders(data.sort, data.dir);
                    document.getElementById('row-count').textContent =
                        Number(data.totalRows).toLocaleString() + ' vendors';
                    document.getElementById('clear-btn').style.display =
                        search ? 'inline' : 'none';
                })
                .catch(() => {
                    document.getElementById('vendor-tbody').style.opacity = '1';
                    document.getElementById('vendor-tbody').innerHTML =
                        '<tr><td colspan="7" class="empty">Failed to load data. Check your connection.</td></tr>';
                });
        }

        function renderTable(data) {
            const tbody = document.getElementById('vendor-tbody');
            if (!data.rows || !data.rows.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">No vendors found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.rows.map(r => `
                <tr>
                    <td class="mono" style="color:var(--text-muted);font-size:12px;">${esc(r.AccountNumber)}</td>
                    <td style="font-weight:500;">${esc(r.Name)}</td>
                    <td>${creditLabel(r.CreditRating)}</td>
                    <td>${r.Preferred ? '<span class="badge badge-purple">Preferred</span>' : '<span class="badge badge-gray">No</span>'}</td>
                    <td>${r.Active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>'}</td>
                    <td class="mono">${r.OrderCount.toLocaleString()}</td>
                    <td class="mono" style="color:var(--accent);">${r.TotalSpent > 0 ? '$' + Number(r.TotalSpent).toLocaleString(undefined, { maximumFractionDigits: 0 }) : '—'}</td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const wrap = document.getElementById('pagination');
            const info = document.getElementById('page-info');
            const btns = document.getElementById('page-buttons');

            if (data.totalPages <= 1) {
                wrap.style.display = 'none';
                return;
            }

            wrap.style.display = 'flex';
            info.textContent = `Page ${data.page} of ${data.totalPages}`;

            let html = '';

            if (data.page > 1) html += btn(data.page - 1, '&#8249;');

            const start = Math.max(1, data.page - 2);
            const end   = Math.min(data.totalPages, data.page + 2);
            for (let i = start; i <= end; i++) {
                html += btn(i, i, i === data.page);
            }

            if (data.page < data.totalPages) html += btn(data.page + 1, '&#8250;');

            btns.innerHTML = html;
        }

        function btn(page, label, active = false) {
            return `<button class="page-btn ${active ? 'active' : ''}" onclick="fetchVendors(${page}, currentSearch, currentSort, currentDir)">${label}</button>`;
        }

        function updateSortHeaders(sort, dir) {
            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('active-asc', 'active-desc');
                if (th.dataset.col === sort) {
                    th.classList.add(dir === 'ASC' ? 'active-asc' : 'active-desc');
                }
            });
        }

        document.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.col;
                const dir = col === currentSort && currentDir === 'ASC' ? 'DESC' : 'ASC';
                fetchVendors(1, currentSearch, col, dir);
            });
        });

        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchVendors(1, this.value.trim(), currentSort, currentDir);
            }, 350);
        });

        function clearSearch() {
            document.getElementById('search-input').value = '';
            fetchVendors(1, '', currentSort, currentDir);
        }

        function creditLabel(r) {
            const labels  = { 1: 'Superior', 2: 'Excellent', 3: 'Above Avg', 4: 'Average', 5: 'Below Avg' };
            const classes = { 1: 'badge-green', 2: 'badge-green', 3: 'badge-blue', 4: 'badge-warn', 5: 'badge-red' };
            return `<span class="badge ${classes[r] || 'badge-gray'}">${labels[r] || r}</span>`;
        }

        function esc(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        fetchVendors(1, '', currentSort, currentDir);
    </script>

<?php require 'footer.php'; ?>