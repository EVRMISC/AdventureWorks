<?php
$pageTitle = 'Customers';
$activePage = 'customers';
require 'header.php';
?>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> sales <span>/</span> customers</div>
            <h2>Customers</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count">Loading...</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search name, ID, territory...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="sortable active-asc" data-col="CustomerID">ID<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Name">Name<span class="sort-icon"></span></th>
                        <th>Type</th>
                        <th class="sortable" data-col="Territory">Territory<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="OrderCount">Orders<span class="sort-icon"></span></th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody id="tbody"><tr><td colspan="6" class="empty">Loading...</td></tr></tbody>
            </table>
            <div id="pagination" class="pagination" style="display:none;">
                <span id="page-info"></span>
                <div id="page-buttons" style="display:flex;gap:4px;margin-left:auto;"></div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1, currentSearch = '', currentSort = 'CustomerID', currentDir = 'ASC', debounce = null;

        function fetch_data(page, search, sort, dir) {
            currentPage = page; currentSearch = search; currentSort = sort; currentDir = dir;
            document.getElementById('tbody').style.opacity = '0.4';
            fetch(`customers_data.php?${new URLSearchParams({page, search, sort, dir})}`)
                .then(r => r.json()).then(data => {
                    document.getElementById('tbody').style.opacity = '1';
                    document.getElementById('row-count').textContent = Number(data.totalRows).toLocaleString() + ' customers';
                    document.getElementById('clear-btn').style.display = search ? 'inline' : 'none';
                    renderTable(data); renderPagination(data); updateSort(data.sort, data.dir);
                });
        }

        function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function renderTable(data) {
            if (!data.rows.length) { document.getElementById('tbody').innerHTML = '<tr><td colspan="6" class="empty">No customers found.</td></tr>'; return; }
            document.getElementById('tbody').innerHTML = data.rows.map(r => `
                <tr>
                    <td class="mono">${r.CustomerID}</td>
                    <td style="font-weight:500;">${esc(r.Name)}</td>
                    <td><span class="badge ${r.AccountType === 'Store' ? 'badge-blue' : 'badge-gray'}">${r.AccountType}</span></td>
                    <td style="font-size:13px;color:var(--text-muted);">${esc(r.Territory) || '—'}</td>
                    <td class="mono">${r.OrderCount}</td>
                    <td class="mono" style="color:var(--accent);">${r.TotalSpent > 0 ? '$'+Number(r.TotalSpent).toLocaleString(undefined,{maximumFractionDigits:0}) : '—'}</td>
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