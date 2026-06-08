<?php
$pageTitle = 'Employees';
$activePage = 'employees';
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

        #search-input {
            transition: border 0.2s;
        }

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
            <div class="breadcrumb"><span>/</span> employees</div>
            <h2>Employees</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header">
                <h3 id="row-count">Loading...</h3>
                <?php if (hasPermission('filter_data')): ?>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="search-box">
                        <span style="color:var(--text-muted);font-size:14px;">⌕</span>
                        <input type="text" id="search-input" placeholder="Search name, title or department...">
                    </div>
                    <button id="clear-btn" onclick="clearSearch()" style="display:none;font-size:13px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px 8px;">✕ Clear</button>
                </div>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable" data-col="BusinessEntityID">#<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="FullName">Name<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="JobTitle">Job Title<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Department">Department<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="SalariedFlag">Type<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="Gender">Gender<span class="sort-icon"></span></th>
                        <th class="sortable active-desc" data-col="HireDate">Hire Date<span class="sort-icon"></span></th>
                        <th class="sortable" data-col="VacationHours">Vacation Hrs<span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="employee-tbody">
                    <tr><td colspan="8" class="empty">Loading...</td></tr>
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
        let currentSort   = 'HireDate';
        let currentDir    = 'DESC';
        let debounceTimer = null;

        function fetchEmployees(page, search, sort, dir) {
            currentPage   = page;
            currentSearch = search;
            currentSort   = sort;
            currentDir    = dir;

            const params = new URLSearchParams({ page, search, sort, dir });

            document.getElementById('employee-tbody').style.opacity = '0.4';

            fetch('employees_data.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    document.getElementById('employee-tbody').style.opacity = '1';
                    renderTable(data);
                    renderPagination(data);
                    updateSortHeaders(data.sort, data.dir);
                    document.getElementById('row-count').textContent =
                        Number(data.totalRows).toLocaleString() + ' active employees';
                    const clearBtn = document.getElementById('clear-btn');
                    if (clearBtn) {
                        clearBtn.style.display = search ? 'inline' : 'none';
                    }
                })
                .catch(() => {
                    document.getElementById('employee-tbody').style.opacity = '1';
                    document.getElementById('employee-tbody').innerHTML =
                        '<tr><td colspan="8" class="empty">Failed to load data. Check your connection.</td></tr>';
                });
        }

        function renderTable(data) {
            const tbody = document.getElementById('employee-tbody');
            if (!data.rows.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty">No employees found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.rows.map(r => `
                <tr>
                    <td class="mono">${r.BusinessEntityID}</td>
                    <td><a href="employee_detail.php?id=${r.BusinessEntityID}" class="row-link">${esc(r.FullName)}</a></td>
                    <td style="font-size:13px;color:var(--text-muted);">${esc(r.JobTitle)}</td>
                    <td>${r.Department ? esc(r.Department) : '<span style="color:var(--text-muted)">—</span>'}</td>
                    <td>
                        <span class="badge ${r.SalariedFlag ? 'badge-green' : 'badge-gray'}">
                            ${r.SalariedFlag ? 'Salaried' : 'Hourly'}
                        </span>
                    </td>
                    <td class="mono">${r.Gender === 'M' ? 'M' : 'F'}</td>
                    <td class="mono">${r.HireDate}</td>
                    <td class="mono">${r.VacationHours}h</td>
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

            if (data.page > 1) {
                html += btn(data.page - 1, '&#8249;');
            }

            const start = Math.max(1, data.page - 2);
            const end   = Math.min(data.totalPages, data.page + 2);

            for (let i = start; i <= end; i++) {
                html += btn(i, i, i === data.page);
            }

            if (data.page < data.totalPages) {
                html += btn(data.page + 1, '&#8250;');
            }

            btns.innerHTML = html;
        }

        function btn(page, label, active = false) {
            return `<button class="page-btn ${active ? 'active' : ''}" onclick="fetchEmployees(${page}, currentSearch, currentSort, currentDir)">${label}</button>`;
        }

        function updateSortHeaders(sort, dir) {
            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('active-asc', 'active-desc');
                if (th.dataset.col === sort) {
                    th.classList.add(dir === 'ASC' ? 'active-asc' : 'active-desc');
                }
            });
        }

        // Conditional filter handlers based on permissions
        if (<?= hasPermission('filter_data') ? 'true' : 'false' ?>) {
            // Sortable column click handler
            document.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const col = th.dataset.col;
                    let dir = 'ASC';
                    if (col === currentSort) {
                        dir = currentDir === 'ASC' ? 'DESC' : 'ASC';
                    }
                    fetchEmployees(1, currentSearch, col, dir);
                });
            });

            // Live search with debounce
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        fetchEmployees(1, this.value.trim(), currentSort, currentDir);
                    }, 350);
                });
            }

            function clearSearch() {
                const searchInput = document.getElementById('search-input');
                if (searchInput) searchInput.value = '';
                fetchEmployees(1, '', currentSort, currentDir);
            }
        } else {
            // Remove sort pointer icons and style for read-only user (Viewer)
            document.querySelectorAll('th.sortable').forEach(th => {
                th.style.cursor = 'default';
                th.classList.remove('sortable');
                const sortIcon = th.querySelector('.sort-icon');
                if (sortIcon) sortIcon.remove();
            });
        }

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        fetchEmployees(1, '', currentSort, currentDir);
    </script>

<?php require 'footer.php'; ?>