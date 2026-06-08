<?php
$pageTitle = 'Purchase Orders';
$activePage = 'purchase_orders';
require 'header.php';
?>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> purchasing <span>/</span> orders</div>
            <h2>Purchase Orders</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count">Loading...</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search vendor or order ID...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="sortable active-desc" data-col="PurchaseOrderID">PO #<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Vendor">Vendor<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Status">Status<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="OrderDate">Order Date<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="ShipDate">Ship Date<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Freight">Freight<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="TotalDue">Total Due<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="tbody"><tr><td colspan="7" class="empty">Loading...</td></tr></tbody>
            </table>
            <div id="pagination" class="pagination" style="display:none;">
                <span id="page-info"></span>
                <div id="page-buttons" style="display:flex;gap:4px;margin-left:auto;"></div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1, currentSearch = '', currentSort = 'PurchaseOrderID', currentDir = 'DESC', debounce = null;

        function fetch_data(page, search, sort, dir) {
            currentPage = page; currentSearch = search; currentSort = sort; currentDir = dir;
            document.getElementById('tbody').style.opacity = '0.4';
            fetch(`purchase_orders_data.php?${new URLSearchParams({page, search, sort, dir})}`)
                .then(r => r.json()).then(data => {
                    document.getElementById('tbody').style.opacity = '1';
                    document.getElementById('row-count').textContent = Number(data.totalRows).toLocaleString() + ' purchase orders';
                    document.getElementById('clear-btn').style.display = search ? 'inline' : 'none';
                    renderTable(data); renderPagination(data); updateSort(data.sort, data.dir);
                });
        }

        function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function renderTable(data) {
            const statusClass = { 'Complete': 'badge-green', 'Rejected': 'badge-red', 'Pending': 'badge-warn', 'Approved': 'badge-blue' };
            if (!data.rows.length) { document.getElementById('tbody').innerHTML = '<tr><td colspan="7" class="empty">No orders found.</td></tr>'; return; }
            document.getElementById('tbody').innerHTML = data.rows.map(r => `
                <tr>
                    <td class="mono" style="color:var(--purple);">PO-${String(r.PurchaseOrderID).padStart(5,'0')}</td>
                    <td style="font-size:13px;">${esc(r.Vendor)}</td>
                    <td><span class="badge ${statusClass[r.Status] || 'badge-gray'}">${r.Status}</span></td>
                    <td class="mono">${r.OrderDate}</td>
                    <td class="mono">${r.ShipDate || '—'}</td>
                    <td class="mono" style="color:var(--text-muted);">$${Number(r.Freight).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                    <td class="mono" style="color:var(--accent);">$${Number(r.TotalDue).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                </tr>`).join('');
        }

        function renderPagination(data) {
            const wrap = document.getElementById('pagination');
            if (data.totalPages <= 1) { wrap.style.display = 'none'; return; }
            wrap.style.display = 'flex';
            document.getElementById('page-info').textContent = `Page ${data.page} of ${data.totalPages}`;
            let html = '';
            if (data.page > 1) html += `<button class="page-btn" onclick="fetch_data(${data.page-1},currentSearch,currentSort,currentDir)">&#8249;</button>`;
            for (let i = Math.max(1,data.page-2); i <= Math.min(data.totalPages,data.page+2); i++)
                html += `<button class="page-btn ${i===data.page?'active':''}" onclick="fetch_data(${i},currentSearch,currentSort,currentDir)">${i}</button>`;
            if (data.page < data.totalPages) html += `<button class="page-btn" onclick="fetch_data(${data.page+1},currentSearch,currentSort,currentDir)">&#8250;</button>`;
            document.getElementById('page-buttons').innerHTML = html;
        }

        function updateSort(sort, dir) {
            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('active-asc','active-desc');
                if (th.dataset.col === sort) th.classList.add(dir === 'ASC' ? 'active-asc' : 'active-desc');
            });
        }

        document.querySelectorAll('th.sortable').forEach(th => th.addEventListener('click', () => {
            const col = th.dataset.col;
            fetch_data(1, currentSearch, col, col === currentSort ? (currentDir === 'ASC' ? 'DESC' : 'ASC') : 'ASC');
        }));

        document.getElementById('search-input').addEventListener('input', function() {
            clearTimeout(debounce); debounce = setTimeout(() => fetch_data(1, this.value.trim(), currentSort, currentDir), 350);
        });

        function clearSearch() { document.getElementById('search-input').value = ''; fetch_data(1, '', currentSort, currentDir); }

        fetch_data(1, '', currentSort, currentDir);
    </script>

<?php require 'footer.php'; ?>