<?php
require 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — AdventureWorks Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f5f5f5;
            --surface: #ffffff;
            --border: #e0e0e0;
            --danger: #ff4f4f;
            --accent: #0066ff;
            --text: #1a1a1a;
            --text-muted: #666666;
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

        .denied-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 100%;
            max-width: 480px;
            padding: 48px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .icon {
            font-size: 64px;
            color: var(--danger);
            margin-bottom: 24px;
            line-height: 1;
        }

        h1 {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-home {
            display: inline-block;
            padding: 12px 28px;
            background: var(--accent);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .btn-home:hover {
            opacity: 0.9;
        }

        .footer-info {
            margin-top: 32px;
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="denied-card">
    <div class="icon">🔒</div>
    <h1>Access Denied</h1>
    <p>Your current account (<strong><?= htmlspecialchars($_SESSION['username']) ?></strong>, assigned role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>) does not have sufficient permissions to view this page or perform this action.</p>
    <a href="index.php" class="btn-home">Return to Reports</a>
    <div class="footer-info">AW2025 · SECURITY LOCK</div>
</div>

</body>
</html>
