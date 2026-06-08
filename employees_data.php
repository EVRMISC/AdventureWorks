<?php
header('Content-Type: application/json');
require_once 'auth.php';
require 'db.php';

// Enforce login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
    exit;
}

// Override search/sort parameters if user lacks filter permission
if (!hasPermission('filter_data')) {
    $_GET['search'] = '';
    $_GET['sort'] = 'HireDate';
    $_GET['dir'] = 'DESC';
}

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['search'] ?? '');

// Whitelist allowed sort columns to prevent SQL injection
$allowedSort = [
    'FullName'         => "p.FirstName + ' ' + p.LastName",
    'JobTitle'         => 'e.JobTitle',
    'Department'       => 'd.Name',
    'SalariedFlag'     => 'e.SalariedFlag',
    'Gender'           => 'e.Gender',
    'HireDate'         => 'e.HireDate',
    'VacationHours'    => 'e.VacationHours',
    'BusinessEntityID' => 'e.BusinessEntityID',
];

$sortKey = $_GET['sort'] ?? 'HireDate';
$sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

if (!array_key_exists($sortKey, $allowedSort)) {
    $sortKey = 'HireDate';
}

$sortExpr = $allowedSort[$sortKey] . ' ' . $sortDir;
$searchParam = "%$search%";

$countQ = sqlsrv_query($conn, "
    SELECT COUNT(*) AS cnt
    FROM HumanResources.Employee e
    JOIN Person.Person p ON p.BusinessEntityID = e.BusinessEntityID
    WHERE e.CurrentFlag = 1
    AND (p.FirstName + ' ' + p.LastName LIKE ? OR e.JobTitle LIKE ? OR d.Name LIKE ?)
    ", [$searchParam, $searchParam, $searchParam]);

// Rebuild with join for department search
$countQ = sqlsrv_query($conn, "
    SELECT COUNT(*) AS cnt
    FROM HumanResources.Employee e
    JOIN Person.Person p ON p.BusinessEntityID = e.BusinessEntityID
    LEFT JOIN HumanResources.EmployeeDepartmentHistory edh 
        ON edh.BusinessEntityID = e.BusinessEntityID AND edh.EndDate IS NULL
    LEFT JOIN HumanResources.Department d ON d.DepartmentID = edh.DepartmentID
    WHERE e.CurrentFlag = 1
    AND (
        p.FirstName + ' ' + p.LastName LIKE ?
        OR e.JobTitle LIKE ?
        OR d.Name LIKE ?
    )
", [$searchParam, $searchParam, $searchParam]);

$countRow   = sqlsrv_fetch_array($countQ, SQLSRV_FETCH_ASSOC);
$totalRows  = $countRow['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$result = sqlsrv_query($conn, "
    SELECT 
        e.BusinessEntityID,
        p.FirstName + ' ' + p.LastName AS FullName,
        e.JobTitle,
        e.Gender,
        CONVERT(varchar, e.HireDate, 23) AS HireDate,
        e.SalariedFlag,
        e.VacationHours,
        d.Name AS Department
    FROM HumanResources.Employee e
    JOIN Person.Person p ON p.BusinessEntityID = e.BusinessEntityID
    LEFT JOIN HumanResources.EmployeeDepartmentHistory edh 
        ON edh.BusinessEntityID = e.BusinessEntityID AND edh.EndDate IS NULL
    LEFT JOIN HumanResources.Department d ON d.DepartmentID = edh.DepartmentID
    WHERE e.CurrentFlag = 1
    AND (
        p.FirstName + ' ' + p.LastName LIKE ?
        OR e.JobTitle LIKE ?
        OR d.Name LIKE ?
    )
    ORDER BY $sortExpr
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
", [$searchParam, $searchParam, $searchParam, $offset, $perPage]);

$rows = [];
while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'BusinessEntityID' => $r['BusinessEntityID'],
        'FullName'         => $r['FullName'],
        'JobTitle'         => $r['JobTitle'],
        'Gender'           => $r['Gender'],
        'HireDate'         => $r['HireDate'],
        'SalariedFlag'     => (bool)$r['SalariedFlag'],
        'VacationHours'    => $r['VacationHours'],
        'Department'       => $r['Department'],
    ];
}

echo json_encode([
    'rows'       => $rows,
    'totalRows'  => $totalRows,
    'totalPages' => $totalPages,
    'page'       => $page,
    'sort'       => $sortKey,
    'dir'        => $sortDir,
]);