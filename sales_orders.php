<?php
$pageTitle = 'Sales Orders';
$activePage = 'sales_orders';
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
            <div class="breadcrumb"><span>/</span> sales <span>/</span> orders</div>
            <h2>Sales Orders</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count">Loading...</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search order ID, customer, PO number...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable active-desc" data-col="SalesOrderID">Order #<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="OrderDate">Order Date<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="DueDate">Due Date<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Status">Status<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="OnlineOrder">Channel<span class="sort-icon"></span></th>
                        <th>Territory</th>
                        <th class="sortable" data-col="TotalDue">Total Due<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="sales-tbody">
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
        let currentSort   = 'SalesOrderID';
        let currentDir    = 'DESC';
        let debounceTimer = null;

        function fetchOrders(page, search, sort, dir) {
            currentPage   = page;
            currentSearch = search;
            currentSort   = sort;
            currentDir    = dir;

            const params = new URLSearchParams({ page, search, sort, dir });

            document.getElementById('sales-tbody').style.opacity = '0.4';

            fetch('sales_orders_data.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('sales-tbody').style.opacity = '1';
                    renderTable(data);
                    renderPagination(data);
                    updateSortHeaders(data.sort, data.dir);
                    document.getElementById('row-count').textContent =
                        Number(data.totalRows).toLocaleString() + ' sales orders';
                    document.getElementById('clear-btn').style.display =
                        search ? 'inline' : 'none';
                })
                .catch(() => {
                    document.getElementById('sales-tbody').style.opacity = '1';
                    document.getElementById('sales-tbody').innerHTML =
                        '<tr><td colspan="7" class="empty">Failed to load data. Check your connection.</td></tr>';
                });
        }

        function renderTable(data) {
            const tbody = document.getElementById('sales-tbody');
            if (!data.rows || !data.rows.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">No orders found.</td></tr>';
                return;
            }

            const statusClass = {
                'Shipped':    'badge-green',
                'Cancelled':  'badge-red',
                'Rejected':   'badge-red',
                'In Process': 'badge-warn',
                'Approved':   'badge-blue',
                'Backordered':'badge-gray',
            };

            tbody.innerHTML = data.rows.map(r => `
                <tr>
                    <td class="mono" style="color:var(--blue);">${esc(r.SalesOrderNumber)}</td>
                    <td class="mono">${esc(r.OrderDate)}</td>
                    <td class="mono">${esc(r.DueDate)}</td>
                    <td><span class="badge ${statusClass[r.Status] || 'badge-gray'}">${esc(r.Status)}</span></td>
                    <td><span class="badge ${r.OnlineOrder ? 'badge-blue' : 'badge-gray'}">${r.OnlineOrder ? 'Online' : 'Offline'}</span></td>
                    <td style="font-size:13px;color:var(--text-muted);">${r.Territory ? esc(r.Territory) : '—'}</td>
                    <td class="mono" style="color:var(--accent);">$${Number(r.TotalDue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
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
            return `<button class="page-btn ${active ? 'active' : ''}" onclick="fetchOrders(${page}, currentSearch, currentSort, currentDir)">${label}</button>`;
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
                fetchOrders(1, currentSearch, col, dir);
            });
        });

        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchOrders(1, this.value.trim(), currentSort, currentDir);
            }, 350);
        });

        function clearSearch() {
            document.getElementById('search-input').value = '';
            fetchOrders(1, '', currentSort, currentDir);
        }

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        fetchOrders(1, '', currentSort, currentDir);
    </script>

<?php require 'footer.php'; ?>