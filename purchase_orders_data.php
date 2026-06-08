<?php
header('Content-Type: application/json');
require 'db.php';

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['search'] ?? '');
$searchParam = "%$search%";

$allowed = [
    'PurchaseOrderID' => 'poh.PurchaseOrderID',
    'OrderDate'       => 'poh.OrderDate',
    'ShipDate'        => 'poh.ShipDate',
    'Status'          => 'poh.Status',
    'TotalDue'        => 'poh.TotalDue',
    'Vendor'          => 'v.Name',
    'Freight'         => 'poh.Freight',
];

$sortKey  = array_key_exists($_GET['sort'] ?? '', $allowed) ? $_GET['sort'] : 'OrderDate';
$sortDir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$sortExpr = $allowed[$sortKey] . ' ' . $sortDir;

$statusMap = [1 => 'Pending', 2 => 'Approved', 3 => 'Rejected', 4 => 'Complete'];

$countQ = sqlsrv_query($conn, "
    SELECT COUNT(*) AS cnt
    FROM Purchasing.PurchaseOrderHeader poh
    JOIN Purchasing.Vendor v ON v.BusinessEntityID = poh.VendorID
    WHERE v.Name LIKE ? OR CAST(poh.PurchaseOrderID AS varchar) LIKE ?
", [$searchParam, $searchParam]);

$totalRows  = sqlsrv_fetch_array($countQ, SQLSRV_FETCH_ASSOC)['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$result = sqlsrv_query($conn, "
    SELECT
        poh.PurchaseOrderID,
        poh.RevisionNumber,
        poh.Status,
        poh.OrderDate,
        poh.ShipDate,
        poh.SubTotal,
        poh.TaxAmt,
        poh.Freight,
        poh.TotalDue,
        v.Name AS Vendor
    FROM Purchasing.PurchaseOrderHeader poh
    JOIN Purchasing.Vendor v ON v.BusinessEntityID = poh.VendorID
    WHERE v.Name LIKE ? OR CAST(poh.PurchaseOrderID AS varchar) LIKE ?
    ORDER BY $sortExpr
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
", [$searchParam, $searchParam, $offset, $perPage]);

$rows = [];
while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'PurchaseOrderID' => $r['PurchaseOrderID'],
        'Status'          => $statusMap[$r['Status']] ?? 'Unknown',
        'StatusCode'      => $r['Status'],
        'OrderDate'       => $r['OrderDate']->format('Y-m-d'),
        'ShipDate'        => $r['ShipDate'] ? $r['ShipDate']->format('Y-m-d') : null,
        'SubTotal'        => round((float)$r['SubTotal'], 2),
        'Freight'         => round((float)$r['Freight'], 2),
        'TotalDue'        => round((float)$r['TotalDue'], 2),
        'Vendor'          => $r['Vendor'],
    ];
}

echo json_encode(['rows' => $rows, 'totalRows' => $totalRows, 'totalPages' => $totalPages, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDir]);