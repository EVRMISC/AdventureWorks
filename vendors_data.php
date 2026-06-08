<?php
// vendors_data.php
// AJAX endpoint for vendors.php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

require 'db.php';
header('Content-Type: application/json');

// --- Inputs ---
$search  = trim($_GET['search'] ?? '');
$sort    = trim($_GET['sort']   ?? 'Name');
$dir     = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// --- Sort whitelist ---
// OrderCount and TotalSpent are aliases from the GROUP BY query,
// so they must be referenced by alias in ORDER BY via a subquery
// Plain column names only — ORDER BY runs on the outer subquery where table aliases don't exist
$sortMap = [
    'AccountNumber' => 'AccountNumber',
    'Name'          => 'Name',
    'CreditRating'  => 'CreditRating',
    'OrderCount'    => 'OrderCount',
    'TotalSpent'    => 'TotalSpent',
];
$sortCol = isset($sortMap[$sort]) ? $sortMap[$sort] : 'Name';
$orderBy = "$sortCol $dir";

// --- Search filter ---
$like         = '%' . $search . '%';
$searchParams = [$like, $like];
$whereClause  = "WHERE v.Name LIKE ? OR v.AccountNumber LIKE ?";

// --- Count ---
$countSql = "
    SELECT COUNT(*) AS total
    FROM Purchasing.Vendor v
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
// Wrap in subquery so ORDER BY can reference OrderCount and TotalSpent aliases
$dataSql = "
    SELECT *
    FROM (
        SELECT
            v.BusinessEntityID AS VendorID,
            v.AccountNumber,
            v.Name,
            v.CreditRating,
            v.PreferredVendorStatus,
            v.ActiveFlag,
            COUNT(poh.PurchaseOrderID) AS OrderCount,
            ISNULL(SUM(poh.TotalDue), 0) AS TotalSpent
        FROM Purchasing.Vendor v
        LEFT JOIN Purchasing.PurchaseOrderHeader poh ON poh.VendorID = v.BusinessEntityID
        $whereClause
        GROUP BY v.BusinessEntityID, v.AccountNumber, v.Name, v.CreditRating, v.PreferredVendorStatus, v.ActiveFlag
    ) AS sub
    ORDER BY $orderBy
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$dataParams = array_merge($searchParams, [$offset, $perPage]);
$dataResult = sqlsrv_query($conn, $dataSql, $dataParams);
if (!$dataResult) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$rows = [];
while ($r = sqlsrv_fetch_array($dataResult, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'VendorID'      => $r['VendorID'],
        'AccountNumber' => $r['AccountNumber'],
        'Name'          => $r['Name'],
        'CreditRating'  => (int)$r['CreditRating'],
        'Preferred'     => (bool)$r['PreferredVendorStatus'],
        'Active'        => (bool)$r['ActiveFlag'],
        'OrderCount'    => (int)$r['OrderCount'],
        'TotalSpent'    => round((float)$r['TotalSpent'], 2),
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