<?php
require 'db.php';

echo "Connected. Checking database schema...\n";

// Check if table exists first
$checkSQL = "SELECT OBJECT_ID('dbo.WebUsers', 'U') AS ObjectId";
$stmt = sqlsrv_query($conn, $checkSQL);
if ($stmt !== false) {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!empty($row['ObjectId'])) {
        echo "Table dbo.WebUsers already exists (ObjectId: {$row['ObjectId']}). Dropping it first...\n";
        sqlsrv_query($conn, "DROP TABLE dbo.WebUsers");
    }
}

// Create table
$createSQL = "
CREATE TABLE dbo.WebUsers (
    UserID INT IDENTITY(1,1) PRIMARY KEY,
    Username NVARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role NVARCHAR(20) NOT NULL CHECK (Role IN ('Admin', 'Analyst', 'Viewer')),
    CreatedAt DATETIME DEFAULT GETDATE()
)
";

$stmt = sqlsrv_query($conn, $createSQL);
if ($stmt === false) {
    echo "Failed to create table. Errors:\n";
    print_r(sqlsrv_errors());
} else {
    echo "Table dbo.WebUsers created successfully.\n";
    
    // Seed
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
}

sqlsrv_close($conn);
?>
