<?php
$pageTitle = 'Shifts';
$activePage = 'shifts';
require 'db.php';

$result = sqlsrv_query($conn, "
    SELECT 
        s.ShiftID,
        s.Name,
        s.StartTime,
        s.EndTime,
        COUNT(edh.BusinessEntityID) AS Assigned
    FROM HumanResources.Shift s
    LEFT JOIN HumanResources.EmployeeDepartmentHistory edh 
        ON edh.ShiftID = s.ShiftID AND edh.EndDate IS NULL
    GROUP BY s.ShiftID, s.Name, s.StartTime, s.EndTime
    ORDER BY s.StartTime
");

require 'header.php';
?>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> shifts</div>
            <h2>Shifts</h2>
        </div>
    </div>

    <div class="content">
        <div class="table-wrap">
            <div class="table-header"><h3>All Shifts</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Shift Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Assigned Employees</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td class="mono"><?= $r['ShiftID'] ?></td>
                        <td><?= htmlspecialchars($r['Name']) ?></td>
                        <td class="mono"><?= $r['StartTime'] instanceof DateTime ? $r['StartTime']->format('H:i') : $r['StartTime'] ?></td>
                        <td class="mono"><?= $r['EndTime'] instanceof DateTime ? $r['EndTime']->format('H:i') : $r['EndTime'] ?></td>
                        <td>
                            <span class="badge <?= $r['Assigned'] > 0 ? 'badge-green' : 'badge-gray' ?>">
                                <?= $r['Assigned'] ?> employees
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require 'footer.php'; ?>