<?php
require 'db.php';

echo "Connected. Running robust table drop and recreate...\n";

// 1. Drop existing table
$dropSQL = "IF OBJECT_ID('dbo.WebUsers', 'U') IS NOT NULL DROP TABLE dbo.WebUsers";
sqlsrv_query($conn, $dropSQL);

// 2. Create table
$createSQL = "
CREATE TABLE dbo.WebUsers (
    UserID INT IDENTITY(1,1) PRIMARY KEY,
    Username NVARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role NVARCHAR(20) NOT NULL CHECK (Role IN ('Admin', 'Analyst', 'Viewer')),
    CreatedAt DATETIME DEFAULT GETDATE()
)
";
sqlsrv_query($conn, $createSQL);

// 3. Verify if table actually exists now
$checkSQL = "SELECT OBJECT_ID('dbo.WebUsers', 'U') AS ObjectId";
$stmt = sqlsrv_query($conn, $checkSQL);
$exists = false;
if ($stmt !== false) {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!empty($row['ObjectId'])) {
        $exists = true;
    }
}

if ($exists) {
    echo "Table dbo.WebUsers verified to exist! Proceeding to seed users...\n";

    $adminHash = password_hash('Admin123!', PASSWORD_BCRYPT);
    $analystHash = password_hash('Analyst123!', PASSWORD_BCRYPT);
    $viewerHash = password_hash('Viewer123!', PASSWORD_BCRYPT);

    $seedSQL = "
    INSERT INTO dbo.WebUsers (Username, PasswordHash, Role) VALUES 
    (?, ?, 'Admin'),
    (?, ?, 'Analyst'),
    (?, ?, 'Viewer')
    ";

    $params = [
        'admin', $adminHash,
        'analyst', $analystHash,
        'viewer', $viewerHash
    ];

    $stmt2 = sqlsrv_query($conn, $seedSQL, $params);
    if ($stmt2 === false) {
        echo "Failed to seed users. Errors:\n";
        print_r(sqlsrv_errors());
    } else {
        echo "Seeded users successfully!\n";
    }
} else {
    echo "Failed to create table. WebUsers does not exist.\n";
}

sqlsrv_close($conn);
?>
