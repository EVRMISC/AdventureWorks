<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the permissions mapping for each role
$ROLE_PERMISSIONS = [
    'Admin' => [
        'view_dashboard' => true,
        'view_reports'   => true,
        'filter_data'    => true,
        'export_reports' => true,
        'manage_users'   => true,
        'manage_settings'=> true
    ],
    'Analyst' => [
        'view_dashboard' => true,
        'view_reports'   => true,
        'filter_data'    => true,
        'export_reports' => true,
        'manage_users'   => false,
        'manage_settings'=> false
    ],
    'Viewer' => [
        'view_dashboard' => true,
        'view_reports'   => true,
        'filter_data'    => false,
        'export_reports' => false,
        'manage_users'   => false,
        'manage_settings'=> false
    ]
];

/**
 * Check if the user is currently logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Require the user to be logged in. Redirect to login.php if they aren't.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if the logged-in user has a specific permission.
 */
function hasPermission($permission) {
    global $ROLE_PERMISSIONS;
    if (!isLoggedIn()) {
        return false;
    }
    $role = $_SESSION['role'];
    return isset($ROLE_PERMISSIONS[$role][$permission]) && $ROLE_PERMISSIONS[$role][$permission] === true;
}

/**
 * Require a specific permission to access a page. Redirect to access_denied.php if not authorized.
 */
function requirePermission($permission) {
    requireLogin();
    if (!hasPermission($permission)) {
        header('Location: access_denied.php');
        exit;
    }
}

/**
 * Get current logged in user details.
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role']
    ];
}
?>
