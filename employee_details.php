<?php
$activePage = 'employees';
require 'db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: employees.php'); exit; }

// Main employee info
$empQ = sqlsrv_query($conn, "
    SELECT 
        e.BusinessEntityID,
        p.FirstName, p.MiddleName, p.LastName,
        e.NationalIDNumber,
        e.LoginID,
        e.JobTitle,
        e.BirthDate,
        e.MaritalStatus,
        e.Gender,
        e.HireDate,
        e.SalariedFlag,
        e.VacationHours,
        e.SickLeaveHours,
        e.CurrentFlag,
        em.EmailAddress
    FROM HumanResources.Employee e
    JOIN Person.Person p ON p.BusinessEntityID = e.BusinessEntityID
    LEFT JOIN Person.EmailAddress em ON em.BusinessEntityID = e.BusinessEntityID
    WHERE e.BusinessEntityID = ?
", [$id]);

$emp = sqlsrv_fetch_array($empQ, SQLSRV_FETCH_ASSOC);
if (!$emp) { header('Location: employees.php'); exit; }

$fullName = trim($emp['FirstName'] . ' ' . ($emp['MiddleName'] ? $emp['MiddleName'] . ' ' : '') . $emp['LastName']);
$pageTitle = $fullName;

// Department history
$deptQ = sqlsrv_query($conn, "
    SELECT 
        d.Name AS Department,
        d.GroupName,
        s.Name AS Shift,
        edh.StartDate,
        edh.EndDate
    FROM HumanResources.EmployeeDepartmentHistory edh
    JOIN HumanResources.Department d ON d.DepartmentID = edh.DepartmentID
    JOIN HumanResources.Shift s ON s.ShiftID = edh.ShiftID
    WHERE edh.BusinessEntityID = ?
    ORDER BY edh.StartDate DESC
", [$id]);

// Pay history
$payQ = sqlsrv_query($conn, "
    SELECT RateChangeDate, Rate, PayFrequency
    FROM HumanResources.EmployeePayHistory
    WHERE BusinessEntityID = ?
    ORDER BY RateChangeDate DESC
", [$id]);

require 'header.php';
?>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> employees <span>/</span> <?= htmlspecialchars($fullName) ?></div>
            <h2><?= htmlspecialchars($fullName) ?></h2>
        </div>
        <span class="badge <?= $emp['CurrentFlag'] ? 'badge-green' : 'badge-red' ?>">
            <?= $emp['CurrentFlag'] ? 'Active' : 'Inactive' ?>
        </span>
    </div>

    <div class="content">
        <a href="employees.php" class="back-btn">← Back to Employees</a>

        <div class="detail-grid">

            <!-- Personal Info -->
            <div class="detail-card">
                <h3>Personal Info</h3>
                <div class="detail-row">
                    <span class="dk">Employee ID</span>
                    <span class="dv" style="font-family:var(--mono)"><?= $emp['BusinessEntityID'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">National ID</span>
                    <span class="dv" style="font-family:var(--mono)"><?= htmlspecialchars($emp['NationalIDNumber']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">Gender</span>
                    <span class="dv"><?= $emp['Gender'] === 'M' ? 'Male' : 'Female' ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">Marital Status</span>
                    <span class="dv"><?= $emp['MaritalStatus'] === 'M' ? 'Married' : 'Single' ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">Birth Date</span>
                    <span class="dv" style="font-family:var(--mono)"><?= $emp['BirthDate']->format('M d, Y') ?></span>
                </div>
                <?php if ($emp['EmailAddress']): ?>
                <div class="detail-row">
                    <span class="dk">Email</span>
                    <span class="dv" style="font-size:13px;"><?= htmlspecialchars($emp['EmailAddress']) ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="dk">Login ID</span>
                    <span class="dv" style="font-family:var(--mono);font-size:12px;"><?= htmlspecialchars($emp['LoginID']) ?></span>
                </div>
            </div>

            <!-- Employment Info -->
            <div class="detail-card">
                <h3>Employment</h3>
                <div class="detail-row">
                    <span class="dk">Job Title</span>
                    <span class="dv"><?= htmlspecialchars($emp['JobTitle']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">Hire Date</span>
                    <span class="dv" style="font-family:var(--mono)"><?= $emp['HireDate']->format('M d, Y') ?></span>
                </div>
                <div class="detail-row">
                    <span class="dk">Type</span>
                    <span class="dv">
                        <span class="badge <?= $emp['SalariedFlag'] ? 'badge-green' : 'badge-gray' ?>">
                            <?= $emp['SalariedFlag'] ? 'Salaried' : 'Hourly' ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="dk">Vacation Hours</span>
                    <span class="dv" style="font-family:var(--mono)"><?= $emp['VacationHours'] ?>h</span>
                </div>
                <div class="detail-row">
                    <span class="dk">Sick Leave Hours</span>
                    <span class="dv" style="font-family:var(--mono)"><?= $emp['SickLeaveHours'] ?>h</span>
                </div>
            </div>

        </div>

        <!-- Department History -->
        <div class="table-wrap" style="margin-bottom:24px;">
            <div class="table-header"><h3>Department History</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Group</th>
                        <th>Shift</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $hasDept = false; while ($r = sqlsrv_fetch_array($deptQ, SQLSRV_FETCH_ASSOC)): $hasDept = true; ?>
                    <tr>
                        <td><?= htmlspecialchars($r['Department']) ?></td>
                        <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars($r['GroupName']) ?></td>
                        <td><?= htmlspecialchars($r['Shift']) ?></td>
                        <td class="mono"><?= $r['StartDate']->format('Y-m-d') ?></td>
                        <td class="mono"><?= $r['EndDate'] ? $r['EndDate']->format('Y-m-d') : '—' ?></td>
                        <td>
                            <?php if (!$r['EndDate']): ?>
                                <span class="badge badge-green">Current</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Past</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$hasDept): ?>
                    <tr><td colspan="6" class="empty">No department history.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pay History -->
        <div class="table-wrap">
            <div class="table-header"><h3>Pay History</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>Effective Date</th>
                        <th>Rate</th>
                        <th>Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $hasPay = false; while ($r = sqlsrv_fetch_array($payQ, SQLSRV_FETCH_ASSOC)): $hasPay = true; ?>
                    <tr>
                        <td class="mono"><?= $r['RateChangeDate']->format('Y-m-d') ?></td>
                        <td class="mono" style="color:var(--accent);">$<?= number_format($r['Rate'], 2) ?></td>
                        <td>
                            <span class="badge badge-gray">
                                <?= $r['PayFrequency'] == 1 ? 'Monthly' : 'Bi-Weekly' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$hasPay): ?>
                    <tr><td colspan="3" class="empty">No pay history.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

<?php require 'footer.php'; ?>