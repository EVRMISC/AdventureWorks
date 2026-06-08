<?php
require 'auth.php';
require 'db.php';

// If already logged in, redirect to index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        // Query the user
        $sql = "SELECT UserID, Username, PasswordHash, Role FROM dbo.WebUsers WHERE Username = ?";
        $stmt = sqlsrv_query($conn, $sql, [$username]);
        
        if ($stmt === false) {
            $error = 'Database connection error. Please contact admin.';
        } else {
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($user && password_verify($password, $user['PasswordHash'])) {
                // Set session variables
                $_SESSION['user_id']  = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role']     = $user['Role'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AdventureWorks Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f5f5f5;
            --surface: #ffffff;
            --border: #e0e0e0;
            --accent: #0066ff;
            --accent-hover: #0044cc;
            --text: #1a1a1a;
            --text-muted: #666666;
            --danger: #ff4f4f;
            --sans: 'IBM Plex Sans', sans-serif;
            --mono: 'IBM Plex Mono', monospace;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo .tag {
            font-family: var(--mono);
            font-size: 14px;
            color: var(--accent);
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .logo h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            color: var(--text);
            background: var(--bg);
            transition: all 0.2s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--accent);
            background: var(--surface);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
        }

        .error-message {
            background: rgba(255, 79, 79, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 24px;
            text-align: center;
        }

        .instructions {
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
            border-top: 1px solid var(--border);
            padding-top: 16px;
            line-height: 1.6;
        }
        .instructions code {
            font-family: var(--mono);
            background: #eee;
            padding: 2px 4px;
            border-radius: 3px;
            color: var(--text);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">
        <div class="tag">REPORTS PORTAL</div>
        <h1>Sign In</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username" placeholder="e.g. admin">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>

        <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <div class="instructions">
        <strong>Demo Accounts:</strong><br>
        Admin: <code>admin</code> / <code>Admin123!</code><br>
        Analyst: <code>analyst</code> / <code>Analyst123!</code><br>
        Viewer: <code>viewer</code> / <code>Viewer123!</code>
    </div>
</div>

</body>
</html>
