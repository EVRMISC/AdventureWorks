<?php
// Migration script: copy AdventureWorks data from local SQL Server to Aiven MySQL
// ---------------------------------------------------------------
// 1. Set unlimited execution time (migration can take a minute)
set_time_limit(0);

// 2. Simple HTML header for progress output
echo "<h3>AdventureWorks SQL Server to MySQL Migrator</h3>\n";

// -----------------------------------------------------------------
// SOURCE: Local SQL Server (SQL Server Native Client must be installed)
// -----------------------------------------------------------------
$sqlServerName = "localhost\\SQLEXPRESS"; // adjust if needed
$sqlConnInfo = [
    "Database" => "AdventureWorks2025",
    "Uid" => "myproject_user",
    "PWD" => "Test123!",
];
$sqlConn = sqlsrv_connect($sqlServerName, $sqlConnInfo);
if ($sqlConn === false) {
    die("❌ SQL Server Connection Failed: " . print_r(sqlsrv_errors(), true));
}
echo "✅ Connected to SQL Server.<br>\n";

// -----------------------------------------------------------------
// TARGET: Aiven MySQL (credentials are already defined in db.php)
// -----------------------------------------------------------------
// Use the $conn variable that db.php creates (it already points to Aiven)
// Ensure db.php is included so we have the $conn PDO instance
require_once __DIR__ . '/db.php';
if ($conn === false) {
    die("❌ Could not establish Aiven MySQL connection. Check db.php credentials.<br>");
}

echo "✅ Connected to Aiven MySQL.<br>\n";

// -----------------------------------------------------------------
// TABLES to migrate – mapping from SQL Server schema.table to MySQL table name
// -----------------------------------------------------------------
$tables = [
    'HumanResources.Shift' => 'HumanResources_Shift',
    'HumanResources.Department' => 'HumanResources_Department',
    'HumanResources.Employee' => 'HumanResources_Employee',
    'HumanResources.EmployeeDepartmentHistory' => 'HumanResources_EmployeeDepartmentHistory',
    'HumanResources.EmployeePayHistory' => 'HumanResources_EmployeePayHistory',
    'Person.Person' => 'Person_Person',
    'Sales.SalesTerritory' => 'Sales_SalesTerritory',
    'Sales.SalesPerson' => 'Sales_SalesPerson',
    'Sales.Customer' => 'Sales_Customer',
    'Sales.SalesOrderHeader' => 'Sales_SalesOrderHeader',
    'Sales.SalesOrderDetail' => 'Sales_SalesOrderDetail',
    'Purchasing.Vendor' => 'Purchasing_Vendor',
    'Purchasing.PurchaseOrderHeader' => 'Purchasing_PurchaseOrderHeader',
    'Purchasing.PurchaseOrderDetail' => 'Purchasing_PurchaseOrderDetail',
];

// -----------------------------------------------------------------
// Helper: map SQL Server data types to MySQL types
// -----------------------------------------------------------------
function mapTypeToMySQL($sqlType, $length, $precision, $scale, $isNullable, $isIdentity) {
    $type = '';
    switch (strtolower($sqlType)) {
        case 'int': case 'integer': $type = 'INT'; break;
        case 'bigint': $type = 'BIGINT'; break;
        case 'smallint': $type = 'SMALLINT'; break;
        case 'tinyint': $type = 'TINYINT'; break;
        case 'bit': $type = 'TINYINT(1)'; break;
        case 'decimal': case 'numeric': $type = "DECIMAL($precision,$scale)"; break;
        case 'money': case 'smallmoney': $type = 'DECIMAL(19,4)'; break;
        case 'float': case 'real': $type = 'DOUBLE'; break;
        case 'varchar': case 'nvarchar':
            $len = ($length == -1 || $length > 4000) ? 255 : $length;
            $type = "VARCHAR($len)"; break;
        case 'char': case 'nchar': $type = "CHAR($length)"; break;
        case 'text': case 'ntext': case 'xml': $type = 'TEXT'; break;
        case 'datetime': case 'datetime2': case 'smalldatetime': $type = 'DATETIME'; break;
        case 'date': $type = 'DATE'; break;
        case 'time': $type = 'TIME'; break;
        case 'uniqueidentifier': $type = 'VARCHAR(36)'; break;
        case 'hierarchyid':
            // Store hierarchyid as base64 encoded string
            $type = 'VARCHAR(255)';
            break;
        case 'varbinary': case 'binary': case 'image': $type = 'BLOB'; break;
        default: $type = 'VARCHAR(255)';
    }
    if ($isIdentity) {
        $type .= ' AUTO_INCREMENT PRIMARY KEY';
    } else {
        $type .= ($isNullable === 'YES') ? ' NULL' : ' NOT NULL';
    }
    return $type;
}

// -----------------------------------------------------------------
// START MIGRATION
// -----------------------------------------------------------------
foreach ($tables as $sqlTable => $myTable) {
    echo "⚙️ Migrating <strong>$sqlTable</strong> → <strong>$myTable</strong>...<br>";

    // 1. Get schema info from SQL Server
    list($schema, $tableName) = explode('.', $sqlTable);
    $schemaSQL = "\n        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, 
               NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE,
               COLUMNPROPERTY(object_id(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') AS IsIdentity
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION\n    ";
    $schemaStmt = sqlsrv_query($sqlConn, $schemaSQL, [$schema, $tableName]);
    if ($schemaStmt === false) {
        echo "❌ Failed to fetch schema for $sqlTable: " . print_r(sqlsrv_errors(), true) . "<br>";
        continue;
    }

    $columns = [];
    $createFields = [];
    $hasIdentity = false;
    while ($col = sqlsrv_fetch_array($schemaStmt, SQLSRV_FETCH_ASSOC)) {
        $colName = $col['COLUMN_NAME'];
        $columns[] = $colName;
        $myFieldType = mapTypeToMySQL(
            $col['DATA_TYPE'],
            $col['CHARACTER_MAXIMUM_LENGTH'],
            $col['NUMERIC_PRECISION'],
            $col['NUMERIC_SCALE'],
            $col['IS_NULLABLE'],
            $col['IsIdentity']
        );
        if ($col['IsIdentity']) $hasIdentity = true;
        $createFields[] = "`$colName` $myFieldType";
    }

    // If no identity column, try to add primary key from SQL Server
    if (!$hasIdentity) {
        $pkSQL = "\n            SELECT k.COLUMN_NAME\n            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k\n            JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS c ON k.CONSTRAINT_NAME = c.CONSTRAINT_NAME\n            WHERE c.CONSTRAINT_TYPE = 'PRIMARY KEY'\n            AND k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ?\n        ";
        $pkStmt = sqlsrv_query($sqlConn, $pkSQL, [$schema, $tableName]);
        if ($pkStmt !== false) {
            $pks = [];
            while ($pkRow = sqlsrv_fetch_array($pkStmt, SQLSRV_FETCH_ASSOC)) {
                $pks[] = "`{$pkRow['COLUMN_NAME']}`";
            }
            if (!empty($pks)) {
                $createFields[] = "PRIMARY KEY (" . implode(', ', $pks) . ")";
            }
        }
    }

    // Drop existing table and create new one in MySQL
    $myConn = $conn; // PDO instance from db.php
    $myConn->exec("DROP TABLE IF EXISTS `$myTable`");
    $createSQL = "CREATE TABLE `$myTable` (\n" . implode(",\n", $createFields) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $myConn->exec($createSQL);
    } catch (PDOException $e) {
        echo "❌ Failed to create table $myTable: " . $e->getMessage() . "<br><pre>$createSQL</pre><br>";
        continue;
    }

    // 2. Fetch data from SQL Server
    $dataSQL = "SELECT * FROM $sqlTable";
    $dataStmt = sqlsrv_query($sqlConn, $dataSQL);
    if ($dataStmt === false) {
        echo "❌ Failed to query data from $sqlTable: " . print_r(sqlsrv_errors(), true) . "<br>";
        continue;
    }

    // Prepare MySQL insert statement
    $placeholders = array_fill(0, count($columns), '?');
    $insertSQL = "INSERT INTO `$myTable` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    $myInsertStmt = $myConn->prepare($insertSQL);

    $rowCount = 0;
    $myConn->beginTransaction();
    while ($row = sqlsrv_fetch_array($dataStmt, SQLSRV_FETCH_NUMERIC)) {
        // Convert DateTime objects to strings for MySQL
        foreach ($row as $k => $v) {
            if ($v instanceof DateTime) {
                $row[$k] = $v->format('Y-m-d H:i:s');
            }
            // If the value is a stream (e.g., hierarchyid), read its contents
            if (is_resource($v)) {
                $streamData = stream_get_contents($v);
                $row[$k] = base64_encode($streamData);
                continue;
            }
            // Encode binary or non‑UTF8 strings as base64
            if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
                $row[$k] = base64_encode($v);
            }
        }
        try {
            $myInsertStmt->execute($row);
            $rowCount++;
        } catch (PDOException $e) {
            echo "⚠️ Failed to insert row in $myTable: " . $e->getMessage() . "<br>";
        }
    }
    $myConn->commit();
    echo "✨ Table <strong>$myTable</strong> created and <strong>$rowCount</strong> rows copied.<br><br>";
}

// ---------------------------------------------------------------
// OPTIONAL: Seed a simple WebUsers table (admin, analyst, viewer)
// ---------------------------------------------------------------
echo "⚙️ Creating and seeding WebUsers table...<br>";
$conn->exec("DROP TABLE IF EXISTS `dbo_WebUsers`");
$conn->exec("\n    CREATE TABLE `dbo_WebUsers` (\n        `UserID` INT AUTO_INCREMENT PRIMARY KEY,\n        `Username` VARCHAR(50) NOT NULL UNIQUE,\n        `PasswordHash` VARCHAR(255) NOT NULL,\n        `Role` VARCHAR(20) NOT NULL,\n        `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4\n");
$adminHash = password_hash('Admin123!', PASSWORD_BCRYPT);
$analystHash = password_hash('Analyst123!', PASSWORD_BCRYPT);
$viewerHash = password_hash('Viewer123!', PASSWORD_BCRYPT);
$seedStmt = $conn->prepare("INSERT INTO `dbo_WebUsers` (Username, PasswordHash, Role) VALUES (?, ?, ?)");
$seedStmt->execute(['admin', $adminHash, 'Admin']);
$seedStmt->execute(['analyst', $analystHash, 'Analyst']);
$seedStmt->execute(['viewer', $viewerHash, 'Viewer']);

echo "✨ WebUsers table created and seeded.<br><br>";

echo "<h3>🎉 DATABASE MIGRATION COMPLETELY SUCCESSFUL!</h3>";

// Close SQL Server connection
sqlsrv_close($sqlConn);
?>
