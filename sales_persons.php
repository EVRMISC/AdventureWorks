<?php
$pageTitle = 'Sales Persons';
$activePage = 'sales_persons';
require 'db.php';

$result = sqlsrv_query($conn, "
    SELECT
        sp.BusinessEntityID,
        p.FirstName + ' ' + p.LastName AS FullName,
        sp.SalesQuota,
        sp.SalesYTD,
        sp.SalesLastYear,
        sp.Bonus,
        sp.CommissionPct,
        st.Name AS Territory,
        st.[Group] AS RegionGroup
    FROM Sales.SalesPerson sp
    JOIN Person.Person p ON p.BusinessEntityID = sp.BusinessEntityID
    LEFT JOIN Sales.SalesTerritory st ON st.TerritoryID = sp.TerritoryID
    ORDER BY sp.SalesYTD DESC
");

$rows = [];
while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $quota = round((float)$r['SalesQuota'], 0);
    $ytd   = round((float)$r['SalesYTD'], 0);
    $pct   = $quota > 0 ? round(($ytd / $quota) * 100) : null;
    $rows[] = [
        'BusinessEntityID' => $r['BusinessEntityID'],
        'FullName'         => $r['FullName'],
        'Territory'        => $r['Territory'] ?? '',
        'RegionGroup'      => $r['RegionGroup'] ?? '',
        'SalesQuota'       => $quota,
        'SalesYTD'         => $ytd,
        'SalesLastYear'    => round((float)$r['SalesLastYear'], 0),
        'Bonus'            => round((float)$r['Bonus'], 0),
        'CommissionPct'    => round((float)$r['CommissionPct'] * 100, 1),
        'QuotaPct'         => $pct,
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
            <div class="breadcrumb"><span>/</span> sales <span>/</span> persons</div>
            <h2>Sales Persons</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count"><?= count($rows) ?> sales persons</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search name or territory...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable" data-col="FullName">Name<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Territory">Territory<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="SalesQuota">Quota<span class="sort-icon"></span></th>
                        <th class="sortable active-desc" data-col="SalesYTD">Sales YTD<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="SalesLastYear">Sales Last Year<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Bonus">Bonus<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="CommissionPct">Commission %<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="QuotaPct">Quota Status<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="persons-tbody"></tbody>
            </table>

            <div id="no-results" style="display:none;text-align:center;padding:24px;color:var(--text-muted);font-size:13px;">
                No sales persons found.
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
                r.FullName.toLowerCase().includes(search) ||
                r.Territory.toLowerCase().includes(search) ||
                r.RegionGroup.toLowerCase().includes(search)
            );

            filtered.sort((a, b) => {
                let av = a[currentSort];
                let bv = b[currentSort];
                // treat null quota pct as -1 so it sorts to the bottom
                if (av === null) av = -1;
                if (bv === null) bv = -1;
                if (typeof av === 'string') av = av.toLowerCase();
                if (typeof bv === 'string') bv = bv.toLowerCase();
                if (av < bv) return currentDir === 'ASC' ? -1 : 1;
                if (av > bv) return currentDir === 'ASC' ? 1 : -1;
                return 0;
            });

            const tbody     = document.getElementById('persons-tbody');
            const noResults = document.getElementById('no-results');

            if (!filtered.length) {
                tbody.innerHTML = '';
                noResults.style.display = 'block';
                document.getElementById('row-count').textContent = '0 sales persons';
                return;
            }

            noResults.style.display = 'none';
            document.getElementById('row-count').textContent =
                filtered.length + ' sales ' + (filtered.length === 1 ? 'person' : 'persons');

            tbody.innerHTML = filtered.map(r => {
                const quotaDisplay = r.SalesQuota > 0
                    ? '$' + r.SalesQuota.toLocaleString()
                    : '—';

                let quotaBadge;
                if (r.QuotaPct === null) {
                    quotaBadge = '<span class="badge badge-gray">No Quota</span>';
                } else if (r.QuotaPct >= 100) {
                    quotaBadge = `<span class="badge badge-green">Met ${r.QuotaPct}%</span>`;
                } else if (r.QuotaPct >= 75) {
                    quotaBadge = `<span class="badge badge-warn">${r.QuotaPct}%</span>`;
                } else {
                    quotaBadge = `<span class="badge badge-red">${r.QuotaPct}%</span>`;
                }

                return `
                    <tr>
                        <td style="font-weight:500;">${esc(r.FullName)}</td>
                        <td style="font-size:13px;color:var(--text-muted);">${r.Territory ? esc(r.Territory) : '—'}</td>
                        <td class="mono">${quotaDisplay}</td>
                        <td class="mono" style="color:var(--blue);">$${r.SalesYTD.toLocaleString()}</td>
                        <td class="mono" style="color:var(--text-muted);">$${r.SalesLastYear.toLocaleString()}</td>
                        <td class="mono" style="color:var(--accent);">$${r.Bonus.toLocaleString()}</td>
                        <td class="mono">${r.CommissionPct}%</td>
                        <td>${quotaBadge}</td>
                    </tr>
                `;
            }).join('');

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
                currentDir  = col === currentSort && currentDir === 'ASC' ? 'DESC' : 'ASC';
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