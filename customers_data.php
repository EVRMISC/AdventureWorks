<?php
header('Content-Type: application/json');
require 'db.php';

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['search'] ?? '');
$searchParam = "%$search%";

$allowed = [
    'CustomerID' => 'c.CustomerID',
    'Name'       => 'COALESCE(s.Name, p.FirstName)',
    'Territory'  => 'st.Name',
    'OrderCount' => 'OrderCount',
];

$sortKey  = array_key_exists($_GET['sort'] ?? '', $allowed) ? $_GET['sort'] : 'CustomerID';
$sortDir  = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$sortExpr = $allowed[$sortKey] . ' ' . $sortDir;

$countQ = sqlsrv_query($conn, "
    SELECT COUNT(*) AS cnt
    FROM Sales.Customer c
    LEFT JOIN Sales.Store s ON s.BusinessEntityID = c.StoreID
    LEFT JOIN Person.Person p ON p.BusinessEntityID = c.PersonID
    LEFT JOIN Sales.SalesTerritory st ON st.TerritoryID = c.TerritoryID
    WHERE COALESCE(s.Name, p.FirstName + ' ' + p.LastName, '') LIKE ?
       OR CAST(c.CustomerID AS varchar) LIKE ?
       OR st.Name LIKE ?
", [$searchParam, $searchParam, $searchParam]);

$totalRows  = sqlsrv_fetch_array($countQ, SQLSRV_FETCH_ASSOC)['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$result = sqlsrv_query($conn, "
    SELECT
        c.CustomerID,
        COALESCE(s.Name, p.FirstName + ' ' + p.LastName) AS Name,
        CASE WHEN c.StoreID IS NOT NULL THEN 'Store' ELSE 'Individual' END AS AccountType,
        st.Name AS Territory,
        COUNT(soh.SalesOrderID) AS OrderCount,
        SUM(soh.TotalDue) AS TotalSpent
    FROM Sales.Customer c
    LEFT JOIN Sales.Store s ON s.BusinessEntityID = c.StoreID
    LEFT JOIN Person.Person p ON p.BusinessEntityID = c.PersonID
    LEFT JOIN Sales.SalesTerritory st ON st.TerritoryID = c.TerritoryID
    LEFT JOIN Sales.SalesOrderHeader soh ON soh.CustomerID = c.CustomerID
    WHERE COALESCE(s.Name, p.FirstName + ' ' + p.LastName, '') LIKE ?
       OR CAST(c.CustomerID AS varchar) LIKE ?
       OR st.Name LIKE ?
    GROUP BY c.CustomerID, s.Name, p.FirstName, p.LastName, c.StoreID, st.Name
    ORDER BY $sortExpr
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
", [$searchParam, $searchParam, $searchParam, $offset, $perPage]);

$rows = [];
while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'CustomerID'  => $r['CustomerID'],
        'Name'        => $r['Name'] ?? '—',
        'AccountType' => $r['AccountType'],
        'Territory'   => $r['Territory'],
        'OrderCount'  => (int)$r['OrderCount'],
        'TotalSpent'  => round((float)$r['TotalSpent'], 2),
    ];
}

echo json_encode(['rows' => $rows, 'totalRows' => $totalRows, 'totalPages' => $totalPages, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDir]);