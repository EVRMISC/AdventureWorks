<?php
// sales_orders_data.php
// AJAX endpoint for sales_orders.php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

require 'db.php';
header('Content-Type: application/json');

// --- Inputs ---
$search  = trim($_GET['search'] ?? '');
$sort    = trim($_GET['sort']   ?? 'SalesOrderID');
$dir     = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// --- Sort whitelist ---
$sortMap = [
    'SalesOrderID' => 'soh.SalesOrderID',
    'OrderDate'    => 'soh.OrderDate',
    'DueDate'      => 'soh.DueDate',
    'Status'       => 'soh.Status',
    'TotalDue'     => 'soh.TotalDue',
    'OnlineOrder'  => 'soh.OnlineOrderFlag',
    'CustomerID'   => 'soh.CustomerID',
];
$sortCol = isset($sortMap[$sort]) ? $sortMap[$sort] : 'soh.SalesOrderID';
$orderBy = "$sortCol $dir";

// --- Search filter ---
$like = '%' . $search . '%';
$searchParams = [$like, $like, $like];

$whereClause = "
    WHERE CAST(soh.SalesOrderID AS VARCHAR) LIKE ?
       OR CAST(soh.CustomerID AS VARCHAR) LIKE ?
       OR soh.PurchaseOrderNumber LIKE ?
";

// --- Count ---
$countSql = "
    SELECT COUNT(*) AS total
    FROM Sales.SalesOrderHeader soh
    $whereClause
";
$countResult = sqlsrv_query($conn, $countSql, $searchParams);
if (!$countResult) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}
$countRow   = sqlsrv_fetch_array($countResult, SQLSRV_FETCH_ASSOC);
$totalRows  = (int)$countRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// --- Data ---
$dataSql = "
    SELECT
        soh.SalesOrderID,
        soh.SalesOrderNumber,
        soh.OrderDate,
        soh.DueDate,
        soh.ShipDate,
        soh.Status,
        soh.OnlineOrderFlag,
        soh.CustomerID,
        soh.TotalDue,
        soh.Freight,
        soh.PurchaseOrderNumber,
        st.Name AS Territory
    FROM Sales.SalesOrderHeader soh
    LEFT JOIN Sales.SalesTerritory st ON st.TerritoryID = soh.TerritoryID
    $whereClause
    ORDER BY $orderBy
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$dataParams = array_merge($searchParams, [$offset, $perPage]);
$dataResult = sqlsrv_query($conn, $dataSql, $dataParams);
if (!$dataResult) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$statusMap = [
    1 => 'In Process',
    2 => 'Approved',
    3 => 'Backordered',
    4 => 'Rejected',
    5 => 'Shipped',
    6 => 'Cancelled',
];

$rows = [];
while ($r = sqlsrv_fetch_array($dataResult, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'SalesOrderID'     => $r['SalesOrderID'],
        'SalesOrderNumber' => $r['SalesOrderNumber'],
        'OrderDate'        => $r['OrderDate']->format('Y-m-d'),
        'DueDate'          => $r['DueDate']->format('Y-m-d'),
        'ShipDate'         => $r['ShipDate'] ? $r['ShipDate']->format('Y-m-d') : null,
        'Status'           => $statusMap[$r['Status']] ?? 'Unknown',
        'StatusCode'       => $r['Status'],
        'OnlineOrder'      => (bool)$r['OnlineOrderFlag'],
        'CustomerID'       => $r['CustomerID'],
        'TotalDue'         => round((float)$r['TotalDue'], 2),
        'Freight'          => round((float)$r['Freight'], 2),
        'PONumber'         => $r['PurchaseOrderNumber'],
        'Territory'        => $r['Territory'],
    ];
}

echo json_encode([
    'rows'       => $rows,
    'totalRows'  => $totalRows,
    'totalPages' => $totalPages,
    'page'       => $page,
    'sort'       => $sort,
    'dir'        => $dir,
]);