<?php
$pageTitle = 'Sales by Territory';
$activePage = 'sales_territory';
require 'db.php';

$result = sqlsrv_query($conn, "
    SELECT
        st.TerritoryID,
        st.Name,
        st.CountryRegionCode,
        st.[Group] AS RegionGroup,
        st.SalesYTD,
        st.SalesLastYear,
        st.CostYTD,
        st.CostLastYear,
        COUNT(DISTINCT soh.SalesOrderID) AS OrderCount,
        COUNT(DISTINCT soh.CustomerID) AS CustomerCount
    FROM Sales.SalesTerritory st
    LEFT JOIN Sales.SalesOrderHeader soh ON soh.TerritoryID = st.TerritoryID
    GROUP BY st.TerritoryID, st.Name, st.CountryRegionCode, st.[Group], st.SalesYTD, st.SalesLastYear, st.CostYTD, st.CostLastYear
    ORDER BY st.SalesYTD DESC
");

$rows = [];
while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'Name'              => $r['Name'],
        'RegionGroup'       => $r['RegionGroup'],
        'CountryRegionCode' => $r['CountryRegionCode'],
        'OrderCount'        => (int)$r['OrderCount'],
        'CustomerCount'     => (int)$r['CustomerCount'],
        'SalesYTD'          => round((float)$r['SalesYTD'], 0),
        'SalesLastYear'     => round((float)$r['SalesLastYear'], 0),
        'CostYTD'           => round((float)$r['CostYTD'], 0),
    ];
}

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
            <div class="breadcrumb"><span>/</span> sales <span>/</span> territory</div>
            <h2>Sales by Territory</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count"><?= count($rows) ?> territories</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search territory or region...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable active-desc" data-col="SalesYTD">Territory<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="RegionGroup">Region Group<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="CountryRegionCode">Country<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="OrderCount">Orders<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="CustomerCount">Customers<span class="sort-icon"></span></th>
                        <th class="sortable active-desc" data-col="SalesYTD">Sales YTD<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="SalesLastYear">Sales Last Year<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="CostYTD">Cost YTD<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="territory-tbody"></tbody>
            </table>

            <div id="no-results" style="display:none;text-align:center;padding:24px;color:var(--text-muted);font-size:13px;">
                No territories found.
            </div>
        </div>
    </div>

    <script>
        const allRows = <?= json_encode($rows) ?>;

        let currentSort   = 'SalesYTD';
        let currentDir    = 'DESC';
        let currentSearch = '';
        let debounceTimer = null;

        function render() {
            const search = currentSearch.toLowerCase();

            let filtered = allRows.filter(r =>
                r.Name.toLowerCase().includes(search) ||
                r.RegionGroup.toLowerCase().includes(search) ||
                r.CountryRegionCode.toLowerCase().includes(search)
            );

            filtered.sort((a, b) => {
                let av = a[currentSort];
                let bv = b[currentSort];
                if (typeof av === 'string') av = av.toLowerCase();
                if (typeof bv === 'string') bv = bv.toLowerCase();
                if (av < bv) return currentDir === 'ASC' ? -1 : 1;
                if (av > bv) return currentDir === 'ASC' ? 1 : -1;
                return 0;
            });

            const tbody = document.getElementById('territory-tbody');
            const noResults = document.getElementById('no-results');

            if (!filtered.length) {
                tbody.innerHTML = '';
                noResults.style.display = 'block';
                document.getElementById('row-count').textContent = '0 territories';
                return;
            }

            noResults.style.display = 'none';
            document.getElementById('row-count').textContent = filtered.length + ' ' + (filtered.length === 1 ? 'territory' : 'territories');

            tbody.innerHTML = filtered.map(r => `
                <tr>
                    <td style="font-weight:600;">${esc(r.Name)}</td>
                    <td style="font-size:13px;color:var(--text-muted);">${esc(r.RegionGroup)}</td>
                    <td class="mono">${esc(r.CountryRegionCode)}</td>
                    <td class="mono">${r.OrderCount.toLocaleString()}</td>
                    <td class="mono">${r.CustomerCount.toLocaleString()}</td>
                    <td class="mono" style="color:var(--blue);">$${r.SalesYTD.toLocaleString()}</td>
                    <td class="mono" style="color:var(--text-muted);">$${r.SalesLastYear.toLocaleString()}</td>
                    <td class="mono" style="color:var(--warning);">$${r.CostYTD.toLocaleString()}</td>
                </tr>
            `).join('');

            updateSortHeaders(currentSort, currentDir);
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
                currentDir = col === currentSort && currentDir === 'ASC' ? 'DESC' : 'ASC';
                currentSort = col;
                render();
            });
        });

        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentSearch = this.value.trim();
                document.getElementById('clear-btn').style.display = currentSearch ? 'inline' : 'none';
                render();
            }, 350);
        });

        function clearSearch() {
            document.getElementById('search-input').value = '';
            currentSearch = '';
            document.getElementById('clear-btn').style.display = 'none';
            render();
        }

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        render();
    </script>

<?php require 'footer.php'; ?>