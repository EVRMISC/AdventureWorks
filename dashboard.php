<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
// Redirected from index.php
require 'db.php';

// Stats queries
$totalEmp   = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM HumanResources.Employee WHERE CurrentFlag = 1");
$totalDept  = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM HumanResources.Department");
$totalShift = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM HumanResources.Shift");
$avgPay     = sqlsrv_query($conn, "
    SELECT AVG(Rate) AS avg_rate 
    FROM HumanResources.EmployeePayHistory ep
    WHERE ep.RateChangeDate = (
        SELECT MAX(ep2.RateChangeDate) 
        FROM HumanResources.EmployeePayHistory ep2 
        WHERE ep2.BusinessEntityID = ep.BusinessEntityID
    )
");

$empRow   = sqlsrv_fetch_array($totalEmp,   SQLSRV_FETCH_ASSOC);
$deptRow  = sqlsrv_fetch_array($totalDept,  SQLSRV_FETCH_ASSOC);
$shiftRow = sqlsrv_fetch_array($totalShift, SQLSRV_FETCH_ASSOC);
$payRow   = sqlsrv_fetch_array($avgPay,     SQLSRV_FETCH_ASSOC);

// Gender breakdown
$genderQ = sqlsrv_query($conn, "
    SELECT Gender, COUNT(*) AS cnt 
    FROM HumanResources.Employee 
    WHERE CurrentFlag = 1
    GROUP BY Gender
");
$genders = [];
while ($r = sqlsrv_fetch_array($genderQ, SQLSRV_FETCH_ASSOC)) {
    $genders[$r['Gender']] = $r['cnt'];
}

// Salaried vs Hourly
$salQ = sqlsrv_query($conn, "
    SELECT SalariedFlag, COUNT(*) AS cnt
    FROM HumanResources.Employee
    WHERE CurrentFlag = 1
    GROUP BY SalariedFlag
");
$sal = [];
while ($r = sqlsrv_fetch_array($salQ, SQLSRV_FETCH_ASSOC)) {
    $sal[$r['SalariedFlag']] = $r['cnt'];
}

// Recent hires
$recentQ = sqlsrv_query($conn, "
    SELECT TOP 8
        p.FirstName + ' ' + p.LastName AS FullName,
        e.JobTitle,
        e.HireDate,
        e.BusinessEntityID
    FROM HumanResources.Employee e
    JOIN Person.Person p ON p.BusinessEntityID = e.BusinessEntityID
    WHERE e.CurrentFlag = 1
    ORDER BY e.HireDate DESC
");

// Top departments by headcount
$deptQ = sqlsrv_query($conn, "
    SELECT TOP 5
        d.Name,
        COUNT(*) AS cnt
    FROM HumanResources.EmployeeDepartmentHistory edh
    JOIN HumanResources.Department d ON d.DepartmentID = edh.DepartmentID
    WHERE edh.EndDate IS NULL
    GROUP BY d.Name
    ORDER BY cnt DESC
");
$deptData = [];
while ($r = sqlsrv_fetch_array($deptQ, SQLSRV_FETCH_ASSOC)) {
    $deptData[] = $r;
}
$maxDept = $deptData[0]['cnt'] ?? 1;

require 'header.php';
?>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> dashboard</div>
            <h2>Overview</h2>
        </div>
    </div>

    <div class="content">

        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Active Employees</div>
                <div class="value"><?= number_format($empRow['cnt']) ?></div>
                <div class="sub">CurrentFlag = 1</div>
            </div>
            <div class="stat-card">
                <div class="label">Departments</div>
                <div class="value"><?= $deptRow['cnt'] ?></div>
                <div class="sub">Across all groups</div>
            </div>
            <div class="stat-card">
                <div class="label">Shifts</div>
                <div class="value"><?= $shiftRow['cnt'] ?></div>
                <div class="sub">Active schedules</div>
            </div>
            <div class="stat-card">
                <div class="label">Avg. Pay Rate</div>
                <div class="value">$<?= number_format($payRow['avg_rate'], 2) ?></div>
                <div class="sub">Per hour / latest rate</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">

            <!-- Gender Breakdown -->
            <div class="table-wrap">
                <div class="table-header"><h3>Gender Breakdown</h3></div>
                <div style="padding:24px;">
                    <?php foreach ($genders as $g => $count): 
                        $label = $g === 'M' ? 'Male' : 'Female';
                        $pct = round(($count / $empRow['cnt']) * 100);
                    ?>
                    <div style="margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <span style="font-size:13px;color:var(--text)"><?= $label ?></span>
                            <span style="font-family:var(--mono);font-size:12px;color:var(--accent)"><?= $count ?> <span style="color:var(--text-muted)">(<?= $pct ?>%)</span></span>
                        </div>
                        <div style="height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?= $pct ?>%;background:var(--accent);border-radius:3px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Departments -->
            <div class="table-wrap">
                <div class="table-header"><h3>Top Departments by Headcount</h3></div>
                <div style="padding:24px;">
                    <?php foreach ($deptData as $d):
                        $pct = round(($d['cnt'] / $maxDept) * 100);
                    ?>
                    <div style="margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <span style="font-size:13px;color:var(--text)"><?= htmlspecialchars($d['Name']) ?></span>
                            <span style="font-family:var(--mono);font-size:12px;color:var(--accent)"><?= $d['cnt'] ?></span>
                        </div>
                        <div style="height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?= $pct ?>%;background:var(--accent-dim);border-radius:3px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Recent Hires -->
        <div class="table-wrap">
            <div class="table-header">
                <h3>Recent Hires</h3>
                <a href="employees.php" style="font-family:var(--mono);font-size:12px;color:var(--accent);text-decoration:none;">View all →</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Hire Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = sqlsrv_fetch_array($recentQ, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><a href="employee_detail.php?id=<?= $r['BusinessEntityID'] ?>" class="row-link"><?= htmlspecialchars($r['FullName']) ?></a></td>
                        <td><?= htmlspecialchars($r['JobTitle']) ?></td>
                        <td class="mono"><?= $r['HireDate']->format('M d, Y') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

<?php require 'footer.php'; ?>