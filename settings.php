<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">⚙️ Settings</h5>
                <a href="dashboard_admin.php" class="btn btn-secondary btn-sm">⬅ Back</a>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="notification_settings.php" class="list-group-item list-group-item-action">
                        📧 Notification Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>

</html>