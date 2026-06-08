<?php
header('Content-Type: application/json');
require 'auth.php';
require 'db.php';

// Enforce admin permission for all endpoints
if (!isLoggedIn() || !hasPermission('manage_users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        $sql = "SELECT UserID, Username, Role, CONVERT(varchar, CreatedAt, 120) AS CreatedAt 
                FROM dbo.WebUsers 
                ORDER BY Username ASC";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to fetch users: ' . print_r(sqlsrv_errors(), true)]);
            exit;
        }

        $users = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $users[] = $r;
        }

        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'create':
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role'] ?? '');

        if (empty($username) || empty($password) || empty($role)) {
            echo json_encode(['success' => false, 'error' => 'All fields are required.']);
            exit;
        }

        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['success' => false, 'error' => 'Username must be at least 3 alphanumeric characters.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
            exit;
        }

        if (!in_array($role, ['Admin', 'Analyst', 'Viewer'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid role selected.']);
            exit;
        }

        // Check if username already exists
        $checkSQL = "SELECT COUNT(*) AS cnt FROM dbo.WebUsers WHERE Username = ?";
        $checkStmt = sqlsrv_query($conn, $checkSQL, [$username]);
        $checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if ($checkRow['cnt'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Username is already taken.']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertSQL = "INSERT INTO dbo.WebUsers (Username, PasswordHash, Role) VALUES (?, ?, ?)";
        $stmt = sqlsrv_query($conn, $insertSQL, [$username, $passwordHash, $role]);

        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to create user.']);
        } else {
            echo json_encode(['success' => true, 'message' => "User '{$username}' created successfully."]);
        }
        break;

    case 'update':
        $userId   = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role'] ?? '');

        if (!$userId || empty($username) || empty($role)) {
            echo json_encode(['success' => false, 'error' => 'Username and role are required.']);
            exit;
        }

        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['success' => false, 'error' => 'Username must be at least 3 alphanumeric characters.']);
            exit;
        }

        if (!in_array($role, ['Admin', 'Analyst', 'Viewer'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid role selected.']);
            exit;
        }

        // Prevent self-demotion
        if ($userId === (int)$_SESSION['user_id'] && $role !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'You cannot remove admin privileges from your own account.']);
            exit;
        }

        // Check unique username (except current user)
        $checkSQL = "SELECT COUNT(*) AS cnt FROM dbo.WebUsers WHERE Username = ? AND UserID != ?";
        $checkStmt = sqlsrv_query($conn, $checkSQL, [$username, $userId]);
        $checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if ($checkRow['cnt'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Username is already taken.']);
            exit;
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
                exit;
            }
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $updateSQL = "UPDATE dbo.WebUsers SET Username = ?, PasswordHash = ?, Role = ? WHERE UserID = ?";
            $params = [$username, $passwordHash, $role, $userId];
        } else {
            $updateSQL = "UPDATE dbo.WebUsers SET Username = ?, Role = ? WHERE UserID = ?";
            $params = [$username, $role, $userId];
        }

        $stmt = sqlsrv_query($conn, $updateSQL, $params);

        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to update user.']);
        } else {
            // Update session if editing self
            if ($userId === (int)$_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $role;
            }
            echo json_encode(['success' => true, 'message' => "User updated successfully."]);
        }
        break;

    case 'delete':
        $userId = (int)($_POST['user_id'] ?? 0);

        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user ID.']);
            exit;
        }

        // Prevent self-deletion
        if ($userId === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'error' => 'You cannot delete the account you are currently logged in with.']);
            exit;
        }

        $deleteSQL = "DELETE FROM dbo.WebUsers WHERE UserID = ?";
        $stmt = sqlsrv_query($conn, $deleteSQL, [$userId]);

        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to delete user.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        break;
}
?>
