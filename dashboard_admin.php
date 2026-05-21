<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$plant = $_GET['plant'] ?? 'assembly';

$stmt = $conn->prepare("SELECT username FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result_admin = $stmt->get_result();
$admin = $result_admin->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f4f6f9;
        }

        .wrapper {
            display: flex;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #2f3542;
            color: white;
            transition: 0.3s;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .text-label {
            display: none;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-radius: 6px;
        }

        .sidebar a:hover {
            background: #57606f;
            color: white;
        }

        .dropdown-menu {
            background: #2f3542;
            border: none;
        }

        .dropdown-menu .dropdown-item {
            color: white;
        }

        .dropdown-menu .dropdown-item:hover {
            background: #57606f;
        }

        .content-area {
            flex: 1;
            padding: 20px;
        }

        .card-machine {
            border-radius: 12px;
            transition: 0.3s;
        }

        .card-machine:hover {
            transform: translateY(-5px);
        }

        .badge-alert {
            background: red;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark bg-dark">

        <div class="container-fluid">

            <button class="btn btn-outline-light" onclick="toggleSidebar()">☰</button>

            <span class="navbar-brand">
                Monitoring System
            </span>

            <div class="text-white">

                <?= htmlspecialchars($admin['username']); ?>

                <a href="logout_admin.php" class="btn btn-danger btn-sm ms-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

            </div>
        </div>
    </nav>

    <div class="wrapper">

        <div id="sidebar" class="sidebar p-3">

            <div class="dropdown mb-3">

                <button class="btn btn-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">
                    <span class="text-label">Plant <?= $plant ?></span>
                </button>

                <ul class="dropdown-menu w-100">

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=assembly">
                            <span>Plant Assembly</span>
                            <span id="plant-badge-1" class="badge badge-alert" style="display:none"></span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=test%20run">
                            <span>Test Run</span>
                            <span id="plant-badge-2" class="badge badge-alert" style="display:none"></span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=packing">
                            <span>Packing</span>
                            <span id="plant-badge-3" class="badge badge-alert" style="display:none"></span>
                        </a>
                    </li>

                </ul>

            </div>

            <hr>

            <a href="history_maintenance_admin.php?plant=<?= $plant ?>">
                📋 <span class="text-label">History Maintenance</span>
            </a>

            <hr>

            <a href="view_monitoring.php?plant=<?= $plant ?>">
                📺 <span class="text-label">View Monitoring</span>
            </a>

            <a href="settings.php">
                ⚙️ <span class="text-label">Settings</span>
            </a>

        </div>

        <div class="content-area">

            <h3 class="mb-3 text-center">
                <i class="fas fa-industry"></i>
                Machine Monitoring Dashboard - <?= ucfirst($plant) ?>
            </h3>

            <div class="text-center mb-4">


            </div>

            <div class="row" id="machine-container"></div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let reworkMode = false;

        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

        function attachSwitchListeners() {

            document.querySelectorAll('.machine-switch').forEach(sw => {

                sw.addEventListener('change', function() {

                    if (reworkMode) {
                        this.checked = !this.checked;
                        return;
                    }

                    const machineId = this.dataset.machineId;
                    const form = this.closest('form');
                    const plant = form.querySelector('input[name="plant"]').value;

                    fetch('update_machine_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `machine_id=${machineId}&plant=${plant}`
                    });

                });

            });

        }

        function updateSwitchState() {

            document.querySelectorAll('.machine-switch').forEach(sw => {
                sw.disabled = reworkMode;
            });

        }

        function loadMachineData() {

            fetch('get_machine_data.php?plant=<?= $plant ?>')
                .then(res => res.text())
                .then(data => {

                    document.getElementById("machine-container").innerHTML = data;

                    attachSwitchListeners();
                    updateSwitchState();

                });

        }

        /* tetap ambil mode dari monitor */
        function loadLineMode() {

            fetch("get_line_mode.php?plant=<?= urlencode($plant) ?>")
                .then(res => res.json())
                .then(data => {

                    reworkMode = data.mode === "rework";
                    updateSwitchState();

                });

        }

        setInterval(loadMachineData, 2000);
        setInterval(loadLineMode, 4000);

        loadMachineData();
        loadLineMode();
    </script>

</body>

</html>