<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

$stmt = $conn->prepare("SELECT username FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result_admin = $stmt->get_result();
$admin = $result_admin->fetch_assoc();

$result = mysqli_query($conn, "SELECT * FROM machine ORDER BY id ASC");
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
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar h6,
        .sidebar span.text-label {
            transition: 0.3s;
        }

        .sidebar.collapsed h6,
        .sidebar.collapsed .text-label {
            display: none;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .dashboard-title {
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light" onclick="toggleSidebar()">☰</button>

            <span class="navbar-brand">
                Machine Monitoring System
            </span>

            <div class="text-white">
                <?= htmlspecialchars($admin['username']); ?>
                |
                <a href="logout_admin.php" class="btn btn-danger btn-sm ms-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="wrapper">

        <!-- SIDEBAR -->
        <div id="sidebar" class="sidebar p-3">

            <h6 class="text-label">Plant List</h6>

            <div class="dropdown mb-3">
                <button class="btn btn-secondary dropdown-toggle w-100"
                    data-bs-toggle="dropdown">
                    <span class="text-label">Select Plant</span>
                </button>
                <ul class="dropdown-menu w-100">
                    <li><a class="dropdown-item" href="dashboard_admin.php?plant=1">Plant 1</a></li>
                    <li><a class="dropdown-item" href="dashboard_admin.php?plant=test%20run">Plant 2</a></li>
                    <li><a class="dropdown-item" href="monitor.php?plant=packing">Plant 3</a></li>
                </ul>
            </div>

            <hr>

            <a href="history_maintenance_admin.php">
                📋 <span class="text-label">History Maintenance</span>
            </a>

            <hr>

            <a href="settings.php">
                ⚙️ <span class="text-label">Settings</span>
            </a>

        </div>

        <!-- CONTENT -->
        <div class="content-area">

            <h3 class="mb-4 text-center dashboard-title">
                <i class="fas fa-industry"></i> Machine Monitoring Dashboard
            </h3>

            <div class="row" id="machine-container">

                <?php while ($row = mysqli_fetch_assoc($result)):

                    if ($row['status'] == 1) {
                        $status_text = "RUNNING";
                        $badge = "success";
                    } elseif ($row['status'] == 2) {
                        $status_text = "ABNORMAL";
                        $badge = "danger";
                    } else {
                        $status_text = "MAINTENANCE";
                        $badge = "warning";
                    }

                    if ($row['repair_status'] == "pending") {
                        $repair_text = "Belum Ditangani";
                        $repair_badge = "danger";
                    } elseif ($row['repair_status'] == "progress") {
                        $repair_text = "Sedang Diperbaiki";
                        $repair_badge = "warning";
                    } elseif ($row['repair_status'] == "done") {
                        $repair_text = "Clear";
                        $repair_badge = "success";
                    } else {
                        $repair_text = "-";
                        $repair_badge = "secondary";
                    }
                ?>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card card-machine shadow">
                            <div class="card-body text-center">

                                <h5><?= htmlspecialchars($row['machine_name']); ?></h5>

                                <form method="POST" action="update_machine_status.php">
                                    <input type="hidden" name="machine_id" value="<?= $row['id']; ?>">

                                    <div class="form-check form-switch d-flex justify-content-center mb-3">
                                        <input class="form-check-input machine-switch"
                                            type="checkbox"
                                            data-machine-id="<?= $row['id']; ?>?
                                            style="transform: scale(1.5);"
                                            <?= ($row['status'] != 1) ? "checked disabled" : "" ?>>
                                    </div>
                                </form>

                                <span class="badge bg-<?= $badge ?> mb-2">
                                    <?= $status_text ?>
                                </span>
                                <br>

                                <span class="badge bg-<?= $repair_badge ?>">
                                    <?= $repair_text ?>
                                </span>

                                <hr>

                                <small class="text-muted">Last Update:</small><br>
                                <small><?= $row['last_update']; ?></small>

                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.querySelectorAll('.machine-switch').forEach(switchEl => {
            switchEl.addEventListener('change', function() {
                const machineId = this.dataset.machineId;
                fetch('update_machine_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `machine_id=${machineId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Status updated');
                            if (data.emailSent) console.log('Email notifikasi dikirim ke Gmail');
                        } else {
                            console.error('Update gagal:', data.message);
                        }
                    })
                    .catch(err => console.error('AJAX error:', err));
            });
        });

        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

        function loadMachineData() {
            fetch('get_machine_data.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('machine-container').innerHTML = data;
                })
                .catch(error => console.log(error));
        }

        setInterval(loadMachineData, 2000);
    </script>

</body>

</html>