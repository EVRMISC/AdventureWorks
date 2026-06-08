<?php
require 'db.php';

echo "Connected to SQL Server successfully.\n";

$dropCreateSQL = "
IF OBJECT_ID('dbo.WebUsers', 'U') IS NOT NULL
    DROP TABLE dbo.WebUsers;

CREATE TABLE dbo.WebUsers (
    UserID INT IDENTITY(1,1) PRIMARY KEY,
    Username NVARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role NVARCHAR(20) NOT NULL CHECK (Role IN ('Admin', 'Analyst', 'Viewer')),
    CreatedAt DATETIME DEFAULT GETDATE()
);
";

$stmt = sqlsrv_query($conn, $dropCreateSQL);
if ($stmt === false) {
    die("Error creating table: " . print_r(sqlsrv_errors(), true));
}
echo "Table dbo.WebUsers created successfully.\n";

$adminHash = password_hash('Admin123!', PASSWORD_BCRYPT);
$analystHash = password_hash('Analyst123!', PASSWORD_BCRYPT);
$viewerHash = password_hash('Viewer123!', PASSWORD_BCRYPT);

$seedSQL = "
INSERT INTO dbo.WebUsers (Username, PasswordHash, Role) VALUES 
(?, ?, 'Admin'),
(?, ?, 'Analyst'),
(?, ?, 'Viewer');
";

$params = [
    'admin', $adminHash,
    'analyst', $analystHash,
    'viewer', $viewerHash
];

$stmt = sqlsrv_query($conn, $seedSQL, $params);
if ($stmt === false) {
    die("Error seeding users: " . print_r(sqlsrv_errors(), true));
}
echo "Seeded 3 user accounts successfully:\n";
echo " - admin / Admin123! (Admin)\n";
echo " - analyst / Analyst123! (Analyst)\n";
echo " - viewer / Viewer123! (Viewer)\n";

sqlsrv_close($conn);
?>
