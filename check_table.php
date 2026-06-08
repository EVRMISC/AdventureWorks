<?php
require 'db.php';
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.WebUsers");
if ($stmt === false) {
    echo "Error querying table: ";
    print_r(sqlsrv_errors());
} else {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo "Success! Table dbo.WebUsers exists and contains: " . $row['cnt'] . " rows.\n";
    
    // Let's print the rows
    $stmt2 = sqlsrv_query($conn, "SELECT UserID, Username, Role FROM dbo.WebUsers");
    while ($r = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        echo " - ID: {$r['UserID']}, User: {$r['Username']}, Role: {$r['Role']}\n";
    }
}
sqlsrv_close($conn);
?>
