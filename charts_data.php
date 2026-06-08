<?php
header('Content-Type: application/json');
require 'db.php';

$type = $_GET['type'] ?? '';

switch ($type) {

    case 'employees_per_department':
        $q = sqlsrv_query($conn, "
            SELECT d.Name AS label, COUNT(*) AS value
            FROM HumanResources.EmployeeDepartmentHistory edh
            JOIN HumanResources.Department d ON d.DepartmentID = edh.DepartmentID
            WHERE edh.EndDate IS NULL
            GROUP BY d.Name
            ORDER BY value DESC
        ");
        echo json_encode(fetchAll($q));
        break;

    case 'gender_breakdown':
        $q = sqlsrv_query($conn, "
            SELECT 
                CASE Gender WHEN 'M' THEN 'Male' ELSE 'Female' END AS label,
                COUNT(*) AS value
            FROM HumanResources.Employee
            WHERE CurrentFlag = 1
            GROUP BY Gender
        ");
        echo json_encode(fetchAll($q));
        break;

    case 'pay_rate_distribution':
        $q = sqlsrv_query($conn, "
            SELECT 
                CASE 
                    WHEN Rate < 15  THEN 'Under $15'
                    WHEN Rate < 25  THEN '$15 - $24'
                    WHEN Rate < 35  THEN '$25 - $34'
                    WHEN Rate < 50  THEN '$35 - $49'
                    WHEN Rate < 75  THEN '$50 - $74'
                    ELSE '$75+'
                END AS label,
                COUNT(*) AS value
            FROM (
                SELECT ep.BusinessEntityID, ep.Rate
                FROM HumanResources.EmployeePayHistory ep
                WHERE ep.RateChangeDate = (
                    SELECT MAX(ep2.RateChangeDate)
                    FROM HumanResources.EmployeePayHistory ep2
                    WHERE ep2.BusinessEntityID = ep.BusinessEntityID
                )
            ) latest
            GROUP BY 
                CASE 
                    WHEN Rate < 15  THEN 'Under $15'
                    WHEN Rate < 25  THEN '$15 - $24'
                    WHEN Rate < 35  THEN '$25 - $34'
                    WHEN Rate < 50  THEN '$35 - $49'
                    WHEN Rate < 75  THEN '$50 - $74'
                    ELSE '$75+'
                END
            ORDER BY MIN(Rate)
        ");
        echo json_encode(fetchAll($q));
        break;

    case 'hires_per_year':
        $q = sqlsrv_query($conn, "
            SELECT YEAR(HireDate) AS label, COUNT(*) AS value
            FROM HumanResources.Employee
            GROUP BY YEAR(HireDate)
            ORDER BY label ASC
        ");
        echo json_encode(fetchAll($q));
        break;

    case 'total_salary':
        $period = $_GET['period'] ?? 'yearly';

        if ($period === 'daily') {
            $q = sqlsrv_query($conn, "
                SELECT TOP 30
                    CONVERT(varchar, RateChangeDate, 23) AS label,
                    SUM(Rate * 8) AS value
                FROM HumanResources.EmployeePayHistory
                GROUP BY RateChangeDate
                ORDER BY RateChangeDate DESC
            ");
            $rows = fetchAll($q);
            $rows = array_reverse($rows);
        } elseif ($period === 'weekly') {
            $q = sqlsrv_query($conn, "
                SELECT TOP 20
                    'Week ' + CAST(DATEPART(week, RateChangeDate) AS varchar)
                    + ' ' + CAST(YEAR(RateChangeDate) AS varchar) AS label,
                    SUM(Rate * 8 * 5) AS value,
                    MIN(RateChangeDate) AS sortdate
                FROM HumanResources.EmployeePayHistory
                GROUP BY DATEPART(week, RateChangeDate), YEAR(RateChangeDate)
                ORDER BY sortdate DESC
            ");
            $rows = fetchAll($q);
            $rows = array_reverse($rows);
        } else {
            // yearly
            $q = sqlsrv_query($conn, "
                SELECT
                    CAST(YEAR(RateChangeDate) AS varchar) AS label,
                    SUM(Rate * 8 * 260) AS value
                FROM HumanResources.EmployeePayHistory
                GROUP BY YEAR(RateChangeDate)
                ORDER BY YEAR(RateChangeDate) ASC
            ");
            $rows = fetchAll($q);
        }

        echo json_encode($rows);
        break;

    default:
        echo json_encode(['error' => 'Unknown chart type']);
}

function fetchAll($q) {
    $rows = [];
    while ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
        $rows[] = ['label' => (string)$r['label'], 'value' => round((float)$r['value'], 2)];
    }
    return $rows;
}
?>