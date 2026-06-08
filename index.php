<?php
$pageTitle = 'Reports';
$activePage = 'reports';
require 'db.php';

$hrCount    = 290;
$salesCount = 121314;
$custCount  = 19128;
$poCount    = 5091;
$vendorCount= 104;
$salesTotal = 226350463.65;
$poTotal    = 26465277.64;

require 'header.php';
?>

    <style>
        .reports-section { margin-bottom: 48px; }

        .section-title {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e0e0e0;
        }

        .section-title h2 { font-size: 20px; font-weight: 700; color: #1a1a1a; text-transform: uppercase; letter-spacing: 1.5px; }

        .section-line { display: none; }

        .section-badge {
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 28px 32px;
            text-decoration: none;
            display: block;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            border-color: #0066ff;
        }

        .report-card::after {
            content: '→';
            position: absolute;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #cccccc;
            transition: all 0.2s ease;
        }

        .report-card:hover { transform: translateY(-4px); }
        .report-card:hover::after { right: 20px; color: #0066ff; }

        .report-card.green:hover { border-color: #0066ff; }
        .report-card.green:hover::after { color: #0066ff; }
        .report-card.blue:hover { border-color: #0066ff; }
        .report-card.blue:hover::after { color: #0066ff; }
        .report-card.purple:hover { border-color: #0066ff; }
        .report-card.purple:hover::after { color: #0066ff; }

        .report-card .rc-title { font-size: 18px; font-weight: 600; color: #1a1a1a; margin-bottom: 8px; transition: color 0.2s; }

        .report-card:hover .rc-title { color: #0066ff; text-shadow: none; }

        .report-card .rc-desc { font-size: 13px; color: #666666; line-height: 1.6; margin-bottom: 16px; }

        .report-card .rc-stat {
            font-family: var(--mono);
            font-size: 12px;
            color: #888888;
            padding-top: 16px;
            border-top: 1px solid #eeeeee;
        }

        .report-card.green .rc-stat strong { color: #0066ff; font-size: 15px; }
        .report-card.blue  .rc-stat strong { color: #0066ff; font-size: 15px; }
        .report-card.purple .rc-stat strong { color: #0066ff; font-size: 15px; }
    </style>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> reports</div>
            <h2>Reports</h2>
        </div>
    </div>

    <div class="content">

        <!-- Human Resources -->
        <div class="reports-section">
            <div class="section-title">
                <h2>Human Resources</h2>
                <div class="section-line"></div>
            </div>
            <div class="report-grid">
                <a href="employees.php" class="report-card green">
                    <div class="rc-title">Employees</div>
                    <div class="rc-desc">Active employees with search, sort by name, title, department, pay type, and hire date.</div>
                    <div class="rc-stat"><strong><?= number_format($hrCount) ?></strong> active employees</div>
                </a>
                <a href="departments.php" class="report-card green">
                    <div class="rc-title">Departments</div>
                    <div class="rc-desc">All departments grouped by organizational group with current headcount per department.</div>
                    <div class="rc-stat">Grouped by org structure</div>
                </a>
                <a href="shifts.php" class="report-card green">
                    <div class="rc-title">Shifts</div>
                    <div class="rc-desc">Work shifts with start and end times and employees currently assigned to each shift.</div>
                    <div class="rc-stat">Start and end times</div>
                </a>
                <a href="pay_history.php" class="report-card green">
                    <div class="rc-title">Pay History</div>
                    <div class="rc-desc">Full pay rate history across all employees sorted by effective date.</div>
                    <div class="rc-stat">Rate changes over time</div>
                </a>
                <a href="charts.php" class="report-card green">
                    <div class="rc-title">HR Charts</div>
                    <div class="rc-desc">Headcount by department, gender split, pay rate ranges, hires per year, and salary totals.</div>
                    <div class="rc-stat">5 interactive charts</div>
                </a>
            </div>
        </div>

        <!-- Sales -->
        <div class="reports-section">
            <div class="section-title">
                <h2>Sales</h2>
                <div class="section-line"></div>
            </div>
            <div class="report-grid">
                <a href="sales_orders.php" class="report-card blue">
                    <div class="rc-title">Sales Orders</div>
                    <div class="rc-desc">All sales orders with status, order date, due date, total amount, and online or offline flag.</div>
                    <div class="rc-stat"><strong><?= number_format($salesCount) ?></strong> orders &middot; $<?= number_format($salesTotal / 1000000, 1) ?>M total</div>
                </a>
                <a href="customers.php" class="report-card blue">
                    <div class="rc-title">Customers</div>
                    <div class="rc-desc">Customer list with store or person account, territory assignment, and total orders per customer.</div>
                    <div class="rc-stat"><strong><?= number_format($custCount) ?></strong> customers</div>
                </a>
                <a href="sales_territory.php" class="report-card blue">
                    <div class="rc-title">Sales by Territory</div>
                    <div class="rc-desc">Sales performance per territory with total sales, cost, and year-to-date figures by region.</div>
                    <div class="rc-stat">By country and region</div>
                </a>
                <a href="sales_persons.php" class="report-card blue">
                    <div class="rc-title">Sales Persons</div>
                    <div class="rc-desc">Sales person quota, YTD sales, bonus, commission rate, and territory assignment.</div>
                    <div class="rc-stat">Quota vs actual performance</div>
                </a>
                <a href="sales_charts.php" class="report-card blue">
                    <div class="rc-title">Sales Charts</div>
                    <div class="rc-desc">Orders over time by year or month, revenue by territory, order status breakdown, online vs offline channel split, and top 10 customers by total revenue.</div>
                    <div class="rc-stat">5 interactive charts</div>
                </a>
            </div>
        </div>

        <!-- Purchasing -->
        <div class="reports-section">
            <div class="section-title">
                <h2>Purchasing</h2>
                <div class="section-line"></div>
            </div>
            <div class="report-grid">
                <a href="purchase_orders.php" class="report-card purple">
                    <div class="rc-title">Purchase Orders</div>
                    <div class="rc-desc">All purchase orders with vendor, status, order date, ship date, total amount, and freight.</div>
                    <div class="rc-stat"><strong><?= number_format($poCount) ?></strong> orders &middot; $<?= number_format($poTotal / 1000000, 1) ?>M total</div>
                </a>
                <a href="vendors.php" class="report-card purple">
                    <div class="rc-title">Vendors</div>
                    <div class="rc-desc">Vendor directory with credit rating, active status, preferred vendor flag, and order count.</div>
                    <div class="rc-stat"><strong><?= number_format($vendorCount) ?></strong> vendors</div>
                </a>
                <a href="vendors_charts.php" class="report-card purple">
                    <div class="rc-title">Vendor Charts</div>
                    <div class="rc-desc">Top vendors by spend, credit rating breakdown, active status split, order volume distribution, and preferred vs non-preferred spend.</div>
                    <div class="rc-stat">5 interactive charts</div>
                </a>
            </div>
        </div>

    </div>

<?php require 'footer.php'; ?>