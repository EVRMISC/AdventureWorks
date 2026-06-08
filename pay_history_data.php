<?php
// pay_history_data.php
// AJAX endpoint for pay_history.php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

require 'db.php';
header('Content-Type: application/json');

// --- Inputs ---
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort    = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'RateChangeDate';
$dir     = isset($_GET['dir'])    && strtoupper($_GET['dir']) === 'ASC' ? 'ASC' : 'DESC';
$page    = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// --- Sort whitelist (full expressions, no alias dependency) ---
$sortMap = [
    'BusinessEntityID' => 'ep.BusinessEntityID',
    'FullName'         => "p.FirstName + ' ' + p.LastName",
    'RateChangeDate'   => 'ep.RateChangeDate',
    'Rate'             => 'ep.Rate',
];
$sortCol = isset($sortMap[$sort]) ? $sortMap[$sort] : 'ep.RateChangeDate';
$orderBy = "$sortCol $dir";

// --- Search filter ---
$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = "(p.FirstName + ' ' + p.LastName LIKE ? OR CAST(ep.BusinessEntityID AS NVARCHAR) LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// --- Count ---
$countSql = "
    SELECT COUNT(*) AS total
    FROM HumanResources.EmployeePayHistory ep
    JOIN Person.Person p ON p.BusinessEntityID = ep.BusinessEntityID
    $where
";
$countResult = sqlsrv_query($conn, $countSql, $params);
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
        ep.BusinessEntityID,
        p.FirstName + ' ' + p.LastName AS FullName,
        CONVERT(VARCHAR(10), ep.RateChangeDate, 120) AS RateChangeDate,
        FORMAT(ep.Rate, 'N2') AS Rate,
        ep.PayFrequency
    FROM HumanResources.EmployeePayHistory ep
    JOIN Person.Person p ON p.BusinessEntityID = ep.BusinessEntityID
    $where
    ORDER BY $orderBy
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$dataParams = array_merge($params, [$offset, $perPage]);
$dataResult = sqlsrv_query($conn, $dataSql, $dataParams);
if (!$dataResult) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$rows = [];
while ($r = sqlsrv_fetch_array($dataResult, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'BusinessEntityID' => $r['BusinessEntityID'],
        'FullName'         => $r['FullName'],
        'RateChangeDate'   => $r['RateChangeDate'],
        'Rate'             => $r['Rate'],
        'PayFrequency'     => $r['PayFrequency'],
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