<?php
// vendors_charts_data.php
// Data endpoint for vendors_charts.php

require 'db.php';
header('Content-Type: application/json');

$type = trim($_GET['type'] ?? '');

switch ($type) {

    // --- Top 10 Vendors by Total Spend ---
    case 'top_vendors_by_spend':
        $r = sqlsrv_query($conn, "
            SELECT TOP 10
                v.Name AS label,
                SUM(poh.TotalDue) AS value
            FROM Purchasing.Vendor v
            JOIN Purchasing.PurchaseOrderHeader poh ON poh.VendorID = v.BusinessEntityID
            GROUP BY v.BusinessEntityID, v.Name
            ORDER BY value DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => round((float)$row['value'], 2)];
        }
        echo json_encode($rows);
        break;

    // --- Vendors by Credit Rating ---
    case 'vendors_by_credit_rating':
        $labelMap = [1 => 'Superior', 2 => 'Excellent', 3 => 'Above Avg', 4 => 'Average', 5 => 'Below Avg'];
        $r = sqlsrv_query($conn, "
            SELECT CreditRating, COUNT(*) AS value
            FROM Purchasing.Vendor
            GROUP BY CreditRating
            ORDER BY CreditRating ASC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'label' => $labelMap[$row['CreditRating']] ?? 'Rating ' . $row['CreditRating'],
                'value' => (int)$row['value'],
            ];
        }
        echo json_encode($rows);
        break;

    // --- Active vs Inactive ---
    case 'active_vs_inactive':
        $r = sqlsrv_query($conn, "
            SELECT
                CASE WHEN ActiveFlag = 1 THEN 'Active' ELSE 'Inactive' END AS label,
                COUNT(*) AS value
            FROM Purchasing.Vendor
            GROUP BY ActiveFlag
            ORDER BY ActiveFlag DESC
        ");
        $rows = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $rows[] = ['label' => $row['label'], 'value' => (int)$row['value']];
        }
        echo json_encode($rows);
        break;

    // --- Order Volume Distribution ---
    // Groups vendors into buckets by how many orders they have placed
    case 'order_volume_distribution':
        $r = sqlsrv_query($conn, "
            SELECT
                CASE
                    WHEN OrderCount = 0        THEN '0 orders'
                    WHEN OrderCount BETWEEN 1 AND 5   THEN '1-5'
                    WHEN OrderCount BETWEEN 6 AND 10  THEN '6-10'
                    WHEN OrderCount BETWEEN 11 AND 20 THEN '11-20'
                    WHEN OrderCount BETWEEN 21 AND 50 THEN '21-50'
                    ELSE '50+'
                END AS label,
                COUNT(*) AS value
            FROM (
                SELECT
                    v.BusinessEntityID,
                    COUNT(poh.PurchaseOrderID) AS OrderCount
                FROM Purchasing.Vendor v
                LEFT JOIN Purchasing.PurchaseOrderHeader poh ON poh.VendorID = v.BusinessEntityID
                GROUP BY v.BusinessEntityID
            ) AS sub
            GROUP BY
                CASE
                    WHEN OrderCount = 0        THEN '0 orders'
                    WHEN OrderCount BETWEEN 1 AND 5   THEN '1-5'
                    WHEN OrderCount BETWEEN 6 AND 10  THEN '6-10'
                    WHEN OrderCount BETWEEN 11 AND 20 THEN '11-20'
                    WHEN OrderCount BETWEEN 21 AND 50 THEN '21-50'
                    ELSE '50+'
                END
        ");
        // Enforce a fixed sort order for the buckets
        $order = ['0 orders', '1-5', '6-10', '11-20', '21-50', '50+'];
        $map   = [];
        while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $map[$row['label']] = (int)$row['value'];
        }
        $rows = [];
        foreach ($order as $label) {
            if (isset($map[$label])) {
                $rows[] = ['label' => $label, 'value' => $map[$label]];
            }
        }
        echo json_encode($rows);
        break;

    // --- Preferred vs Non-Preferred Spend ---
    case 'preferred_vs_nonpreferred_spend':
        $r = sqlsrv_query($conn, "
            SELECT
                CASE WHEN v.PreferredVendorStatus = 1 THEN 'Preferred' ELSE 'Non-Preferred' END AS label,
                SUM(poh.TotalDue) AS value
            FROM Purchasing.Vendor v
            JOIN Purchasing.PurchaseOrderHeader poh ON poh.VendorID = v.BusinessEntityID
            GROUP BY v.PreferredVendorStatus
            ORDER BY v.PreferredVendorStatus DESC
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