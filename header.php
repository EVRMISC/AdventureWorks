<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Portal' ?> — AdventureWorks</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f5f5f5;
            --surface: #ffffff;
            --surface2: #eeeeee;
            --border: #cccccc;
            --accent: #0066ff;
            --accent-dim: #0044cc;
            --text: #1a1a1a;
            --text-muted: #666666;
            --danger: #ff4f4f;
            --warning: #f5a623;
            --blue: #0066ff;
            --purple: #0066ff;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'IBM Plex Sans', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }

        .sidebar-logo {
            padding: 28px 24px 24px;
            border-bottom: 2px solid #0066ff;
            background: linear-gradient(135deg, #0066ff 0%, #0044cc 100%);
        }

        .sidebar-logo .tag {
            font-family: var(--mono);
            font-size: 16px;
            color: #ffffff;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .sidebar-logo h1 {
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            line-height: 1.5;
        }

        .sidebar-nav {
            padding: 12px 12px;
            flex: 1;
        }

        .nav-label {
            font-family: var(--mono);
            font-size: 16px;
            color: #0066ff;
            font-weight: bold;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 12px 12px 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #555555;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .nav-link:hover { background: #e8f0fe; color: #0066ff; }
        .nav-link.active { background: #0066ff; color: #ffffff; font-weight: 600; }
        .nav-link.active-blue { background: #0066ff; color: #ffffff; font-weight: 600; }
        .nav-link.active-purple { background: #0066ff; color: #ffffff; font-weight: 600; }

        .nav-link .icon { width: 16px; text-align: center; font-size: 13px; }

        .nav-divider {
            height: 1px;
            background: var(--border);
            margin: 10px 12px;
        }

        .sidebar-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border);
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
        }

        /* MAIN */
        .main { margin-left: 240px; flex: 1; min-height: 100vh; }

        .topbar {
            padding: 24px 40px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        .topbar h2 { font-size: 26px; font-weight: 700; color: #1a1a1a; text-transform: uppercase; letter-spacing: 1.5px; }

        .topbar .breadcrumb { display: none; }

        .content { padding: 32px 40px; }

        /* STAT CARDS */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--accent);
        }

        .stat-card.blue::before  { background: var(--blue); }
        .stat-card.purple::before { background: var(--purple); }
        .stat-card.warn::before  { background: var(--warning); }

        .stat-card .label {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .stat-card .value {
            font-family: var(--mono);
            font-size: 32px;
            font-weight: 600;
            color: var(--accent);
            line-height: 1;
        }

        .stat-card.blue .value  { color: var(--blue); }
        .stat-card.purple .value { color: var(--purple); }
        .stat-card.warn .value  { color: var(--warning); }
        .stat-card .sub { font-size: 12px; color: var(--text-muted); margin-top: 8px; }

        /* TABLE */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0;
            overflow: hidden;
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .table-header h3 { font-size: 15px; font-weight: 600; color: var(--text); }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 14px;
            min-width: 260px;
            transition: border 0.2s;
        }

        .search-box:focus-within { border-color: var(--accent); }
        .search-box input { background: none; border: none; outline: none; color: var(--text); font-family: var(--sans); font-size: 14px; width: 100%; }
        .search-box input::placeholder { color: var(--text-muted); }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 12px 24px;
            text-align: left;
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }

        th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        th.sortable:hover { color: var(--text); background: #232b28; }
        th.sortable .sort-icon { display: inline-block; margin-left: 5px; font-size: 10px; color: var(--text-muted); }
        th.sortable.active-asc .sort-icon::after  { content: '▲'; color: var(--accent); }
        th.sortable.active-desc .sort-icon::after { content: '▼'; color: var(--accent); }
        th.sortable:not(.active-asc):not(.active-desc) .sort-icon::after { content: '⇅'; }

        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--surface2); }
        tbody tr { animation: fadeRow 0.15s ease; }

        @keyframes fadeRow {
            from { opacity: 0; transform: translateY(3px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        tbody td { padding: 13px 24px; font-size: 14px; color: var(--text); }
        tbody td.mono { font-family: var(--mono); font-size: 13px; color: var(--text-muted); }

        /* BADGES */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-family: var(--mono); font-size: 11px; font-weight: 500; }
        .badge-green  { background: rgba(0,229,160,0.12); color: var(--accent); }
        .badge-gray   { background: rgba(255,255,255,0.06); color: var(--text-muted); }
        .badge-red    { background: rgba(255,79,79,0.12); color: var(--danger); }
        .badge-warn   { background: rgba(245,166,35,0.12); color: var(--warning); }
        .badge-blue   { background: rgba(79,168,255,0.12); color: var(--blue); }
        .badge-purple { background: rgba(196,77,255,0.12); color: var(--purple); }

        a.row-link { color: var(--accent); text-decoration: none; font-weight: 500; }
        a.row-link:hover { text-decoration: underline; }

        /* DETAIL CARD */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .detail-card { background: var(--surface); border: 1px solid var(--border); border-radius: 0; padding: 24px; }
        .detail-card h3 { font-size: 13px; font-family: var(--mono); color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid rgba(42,51,48,0.5); gap: 16px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .dk { font-size: 12px; color: var(--text-muted); font-family: var(--mono); white-space: nowrap; }
        .detail-row .dv { font-size: 14px; color: var(--text); text-align: right; }

        /* PAGINATION */
        .pagination { display: flex; align-items: center; gap: 8px; padding: 20px 24px; border-top: 1px solid var(--border); font-size: 13px; color: var(--text-muted); }
        .page-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border); background: none; color: var(--text-muted); text-decoration: none; font-size: 13px; cursor: pointer; transition: all 0.15s; }
        .page-btn:hover { border-color: var(--accent); color: var(--accent); }
        .page-btn.active { background: var(--accent); color: var(--bg); border-color: var(--accent); font-weight: 600; }

        .empty { padding: 60px 24px; text-align: center; color: var(--text-muted); font-family: var(--mono); font-size: 13px; }

        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); text-decoration: none; font-size: 13px; margin-bottom: 24px; transition: color 0.15s; }
        .back-btn:hover { color: var(--accent); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="tag" style="font-size: 22px; letter-spacing: 2px; padding: 8px 0; margin-bottom: 8px; font-weight: bold;">REPORTS PORTAL</div>
        <h1 style="font-size: 13px; margin-bottom: 2px;">JEROME AUTIDA</h1>
        <h1 style="font-size: 13px; margin-bottom: 4px;">MARC JASON GONZALES</h1>
        
        <!-- User Profile Info -->
        <div style="margin-top: 12px; padding: 10px 12px; background: rgba(0, 102, 255, 0.08); border-radius: 6px; border: 1px solid rgba(0, 102, 255, 0.15);">
            <div style="font-size: 10px; font-family: var(--mono); color: #0066ff; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Logged In As</div>
            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a; margin-top: 2px;"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div style="display: inline-block; font-size: 10px; font-family: var(--mono); background: #0066ff; color: #ffffff; padding: 2px 6px; border-radius: 4px; margin-top: 6px; text-transform: uppercase; font-weight: 600;"><?= htmlspecialchars($_SESSION['role']) ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">

        <div class="nav-label">General</div>
        <a href="index.php" class="nav-link <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">▤</span> Reports
        </a>
        <a href="dashboard.php" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">▦</span> Dashboard
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Human Resources</div>
        <a href="employees.php" class="nav-link <?= ($activePage ?? '') === 'employees' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">◉</span> Employees
        </a>
        <a href="departments.php" class="nav-link <?= ($activePage ?? '') === 'departments' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">◈</span> Departments
        </a>
        <a href="shifts.php" class="nav-link <?= ($activePage ?? '') === 'shifts' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">◷</span> Shifts
        </a>
        <a href="pay_history.php" class="nav-link <?= ($activePage ?? '') === 'pay' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">◎</span> Pay History
        </a>
        <a href="charts.php" class="nav-link <?= ($activePage ?? '') === 'charts' ? 'active' : '' ?>">
            <span class="icon" style="display:none;">◱</span> HR Charts
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Sales</div>
        <a href="sales_orders.php" class="nav-link <?= ($activePage ?? '') === 'sales_orders' ? 'active-blue' : '' ?>">
            <span class="icon" style="display:none;">◆</span> Sales Orders
        </a>
        <a href="customers.php" class="nav-link <?= ($activePage ?? '') === 'customers' ? 'active-blue' : '' ?>">
            <span class="icon" style="display:none;">◇</span> Customers
        </a>
        <a href="sales_territory.php" class="nav-link <?= ($activePage ?? '') === 'sales_territory' ? 'active-blue' : '' ?>">
            <span class="icon" style="display:none;">◐</span> Territory
        </a>
        <a href="sales_persons.php" class="nav-link <?= ($activePage ?? '') === 'sales_persons' ? 'active-blue' : '' ?>">
            <span class="icon" style="display:none;">◑</span> Sales Persons
        </a>
        <a href="sales_charts.php" class="nav-link <?= ($activePage ?? '') === 'sales_charts' ? 'active-blue' : '' ?>">
            <span class="icon" style="display:none;">◱</span> Sales Charts
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Purchasing</div>
        <a href="purchase_orders.php" class="nav-link <?= ($activePage ?? '') === 'purchase_orders' ? 'active-purple' : '' ?>">
            <span class="icon" style="display:none;">◭</span> Purchase Orders
        </a>
        <a href="vendors.php" class="nav-link <?= ($activePage ?? '') === 'vendors' ? 'active-purple' : '' ?>">
            <span class="icon" style="display:none;">◬</span> Vendors
        </a>
        <a href="vendors_charts.php" class="nav-link <?= ($activePage ?? '') === 'vendors_charts' ? 'active-purple' : '' ?>">
            <span class="icon" style="display:none;">◱</span> Vendor Charts
        </a>

        <!-- Conditional Admin Section -->
        <?php if (hasPermission('manage_users')): ?>
            <div class="nav-divider"></div>
            <div class="nav-label">System Admin</div>
            <a href="users.php" class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
                <span class="icon" style="display:none;">⚙</span> User Accounts
            </a>
        <?php endif; ?>

        <!-- Sign Out link -->
        <div class="nav-divider"></div>
        <a href="logout.php" class="nav-link" style="color: var(--danger);">
            <span class="icon" style="display:none;">⎋</span> Sign Out
        </a>

    </nav>
    <div class="sidebar-footer">AW2025 · <?= htmlspecialchars($_SESSION['role']) ?></div>
</aside>

<div class="main">