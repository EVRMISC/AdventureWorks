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
