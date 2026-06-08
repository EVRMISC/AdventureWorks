<?php
$myHost = getenv('DB_HOST') ?: 'mysql-284fd505-marcjasongonzales19-1056.g.aivencloud.com';
$myPort = getenv('DB_PORT') ?: 14001;
$myDb   = getenv('DB_NAME') ?: 'defaultdb';
$myUser = getenv('DB_USER') ?: 'avnadmin';
$myPass = getenv('DB_PASS') ?: 'ENTER_PASSWORD_HERE';

// Path to CA certificate (included in repo)
$caPath = __DIR__ . '/' . (getenv('DB_CA_PATH') ?: 'ca.pem');

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::MYSQL_ATTR_SSL_CA => $caPath,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

try {
    $dsn = "mysql:host=$myHost;port=$myPort;dbname=$myDb;charset=utf8mb4";
    $conn = new PDO($dsn, $myUser, $myPass, $pdoOptions);
} catch (PDOException $e) {
    die('MySQL connection failed: ' . $e->getMessage());
}

// POLYFILL FOR SQL SERVER FUNCTIONS TO USE MYSQL (PDO)
if (!defined('SQLSRV_FETCH_ASSOC')) define('SQLSRV_FETCH_ASSOC', PDO::FETCH_ASSOC);
if (!defined('SQLSRV_FETCH_NUM')) define('SQLSRV_FETCH_NUM', PDO::FETCH_NUM);
if (!defined('SQLSRV_FETCH_BOTH')) define('SQLSRV_FETCH_BOTH', PDO::FETCH_BOTH);

if (!function_exists('sqlsrv_query')) {
    function sqlsrv_query($conn, $tsql, $params = []) {
        // Map SQL Server schema paths (Schema.Table) to MySQL table names (Schema_Table)
        $tsql = str_replace(
            ['dbo.', 'HumanResources.', 'Person.', 'Sales.', 'Purchasing.'], 
            ['dbo_', 'HumanResources_', 'Person_', 'Sales_', 'Purchasing_'], 
            $tsql
        );
        try {
            if (empty($params)) {
                $stmt = $conn->query($tsql);
                return $stmt ?: false;
            } else {
                $stmt = $conn->prepare($tsql);
                $success = $stmt->execute($params);
                return $success ? $stmt : false;
            }
        } catch (PDOException $e) {
            $GLOBALS['sqlsrv_last_error'] = $e->getMessage();
            return false;
        }
    }
}

if (!function_exists('sqlsrv_fetch_array')) {
    function sqlsrv_fetch_array($stmt, $fetchType = SQLSRV_FETCH_BOTH) {
        if (!$stmt) return false;
        return $stmt->fetch($fetchType);
    }
}

if (!function_exists('sqlsrv_has_rows')) {
    function sqlsrv_has_rows($stmt) {
        if (!$stmt) return false;
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('sqlsrv_free_stmt')) {
    function sqlsrv_free_stmt($stmt) {
        if ($stmt) $stmt->closeCursor();
        return true;
    }
}

if (!function_exists('sqlsrv_errors')) {
    function sqlsrv_errors() {
        return [['message' => $GLOBALS['sqlsrv_last_error'] ?? 'Unknown database error']];
    }
}
