<?php
// departments_data.php
// AJAX endpoint for departments.php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

require 'db.php';
header('Content-Type: application/json');

// --- Inputs ---
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort    = isset($_GET['sort'])   ? trim($_GET['sort'])   : 'Name';
$dir     = isset($_GET['dir'])    && strtoupper($_GET['dir']) === 'DESC' ? 'DESC' : 'ASC';
$page    = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

// --- Sort whitelist ---
// Plain column names only — ORDER BY is on the outer subquery where table aliases don't exist
$sortMap = [
    'DepartmentID' => 'DepartmentID',
    'Name'         => 'Name',
    'GroupName'    => 'GroupName',
    'HeadCount'    => 'HeadCount',
];
$sortCol = isset($sortMap[$sort]) ? $sortMap[$sort] : 'Name';
$orderBy = "$sortCol $dir";

// --- Search filter ---
$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = "(d.Name LIKE ? OR d.GroupName LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// --- Count ---
$countSql = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT d.DepartmentID
        FROM HumanResources.Department d
        LEFT JOIN HumanResources.EmployeeDepartmentHistory edh
            ON edh.DepartmentID = d.DepartmentID AND edh.EndDate IS NULL
        $where
        GROUP BY d.DepartmentID, d.Name, d.GroupName
    ) AS sub
";
$countResult = sqlsrv_query($conn, $countSql, $params);
if (!$countResult) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}
$countRow  = sqlsrv_fetch_array($countResult, SQLSRV_FETCH_ASSOC);
$totalRows = (int)$countRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// --- Data ---
// HeadCount alias only works in ORDER BY via subquery when sorting by it
$dataSql = "
    SELECT *
    FROM (
        SELECT
            d.DepartmentID,
            d.Name,
            d.GroupName,
            COUNT(edh.BusinessEntityID) AS HeadCount
        FROM HumanResources.Department d
        LEFT JOIN HumanResources.EmployeeDepartmentHistory edh
            ON edh.DepartmentID = d.DepartmentID AND edh.EndDate IS NULL
        $where
        GROUP BY d.DepartmentID, d.Name, d.GroupName
    ) AS sub
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
        'DepartmentID' => $r['DepartmentID'],
        'Name'         => $r['Name'],
        'GroupName'    => $r['GroupName'],
        'HeadCount'    => $r['HeadCount'],
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