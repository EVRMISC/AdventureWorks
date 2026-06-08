<?php
// sales_charts_data.php
// Data endpoint for sales_charts.php

require 'db.php';
header('Content-Type: application/json');

$type   = trim($_GET['type']   ?? '');
$period = trim($_GET['period'] ?? 'yearly');

switch ($type) {

    // --- Orders Over Time ---
    // Returns order count grouped by year or month
    case 'orders_over_time':
        if ($period === 'monthly') {
            $sql = "
                SELECT
                    FORMAT(OrderDate, 'yyyy-MM') AS label,
                    COUNT(*) AS value
                FROM Sales.SalesOrderHeader
                GROUP BY FORMAT(OrderDate, 'yyyy-MM')
                ORDER BY label ASC
            ";
        } else {
            $sql = "
                SELECT
                    CAST(YEAR(OrderDate) AS VARCHAR) AS label,
                    COUNT(*) AS value
                FROM Sales.SalesOrderHeader
                GROUP BY YEAR(OrderDate)
                ORDER BY YEAR(OrderDate) ASC
            ";
        }
        $r = sqlsrv_query($conn, $sql);
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => (int)$row['value']];
        }
        echo json_encode($rows);
        break;

    // --- Revenue by Territory ---
    // Total TotalDue per territory, sorted descending
    case 'revenue_by_territory':
        $r = sqlsrv_query($conn, "
            SELECT
                st.Name AS label,
                SUM(soh.TotalDue) AS value
            FROM Sales.SalesOrderHeader soh
            JOIN Sales.SalesTerritory st ON st.TerritoryID = soh.TerritoryID
            GROUP BY st.Name
            ORDER BY value DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => round((float)$row['value'], 2)];
        }
        echo json_encode($rows);
        break;

    // --- Order Status Breakdown ---
    // Count of orders per status code
    case 'order_status_breakdown':
        $statusMap = [
            1 => 'In Process',
            2 => 'Approved',
            3 => 'Backordered',
            4 => 'Rejected',
            5 => 'Shipped',
            6 => 'Cancelled',
        ];
        $r = sqlsrv_query($conn, "
            SELECT Status, COUNT(*) AS value
            FROM Sales.SalesOrderHeader
            GROUP BY Status
            ORDER BY value DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'label' => $statusMap[$row['Status']] ?? 'Unknown',
                'value' => (int)$row['value'],
            ];
        }
        echo json_encode($rows);
        break;

    // --- Online vs Offline Orders ---
    case 'online_vs_offline':
        $r = sqlsrv_query($conn, "
            SELECT
                CASE WHEN OnlineOrderFlag = 1 THEN 'Online' ELSE 'Offline' END AS label,
                COUNT(*) AS value
            FROM Sales.SalesOrderHeader
            GROUP BY OnlineOrderFlag
            ORDER BY OnlineOrderFlag DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => (int)$row['value']];
        }
        echo json_encode($rows);
        break;

    // --- Top 10 Customers by Revenue ---
    // Joins to Person or Store name, picks whichever is available
    case 'top_customers':
        $r = sqlsrv_query($conn, "
            SELECT TOP 10
                COALESCE(s.Name, p.FirstName + ' ' + p.LastName, 'Customer ' + CAST(c.CustomerID AS VARCHAR)) AS label,
                SUM(soh.TotalDue) AS value
            FROM Sales.SalesOrderHeader soh
            JOIN Sales.Customer c ON c.CustomerID = soh.CustomerID
            LEFT JOIN Sales.Store s ON s.BusinessEntityID = c.StoreID
            LEFT JOIN Person.Person p ON p.BusinessEntityID = c.PersonID
            GROUP BY c.CustomerID, s.Name, p.FirstName, p.LastName
            ORDER BY value DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => round((float)$row['value'], 2)];
        }
        echo json_encode($rows);
        break;

    default:
        echo json_encode(['error' => 'Unknown chart type: ' . htmlspecialchars($type)]);
        break;
}