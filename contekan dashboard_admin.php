<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$plant = isset($_GET['plant']) ? (int)$_GET['plant'] : 1;

$stmt = $conn->prepare("SELECT username FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result_admin = $stmt->get_result();
$admin = $result_admin->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM machine WHERE plant=? ORDER BY id ASC");
$stmt->bind_param("i", $plant);
$stmt->execute();
$result = $stmt->get_result();
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
            overflow: hidden;
        }

        .wrapper {
            display: flex;
            height: 100vh;
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
            height: calc(100vh - 56px);
            overflow-y: auto;
            scroll-behavior: smooth;
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
                Machine Monitoring System - Plant <?= $plant ?>
            </span>

            <div class="text-white">

                <?= htmlspecialchars($admin['username']); ?> |

                <a href="logout_admin.php" class="btn btn-danger btn-sm ms-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

            </div>

        </div>
    </nav>

    <div class="wrapper">

        <!-- SIDEBAR -->

        <div id="sidebar" class="sidebar p-3">

            <div class="dropdown mb-3">

                <button class="btn btn-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">
                    <span class="text-label">Plant <?= $plant ?></span>
                </button>

                <ul class="dropdown-menu w-100">

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=1">
                            <span>Plant 1</span>
                            <span id="plant-badge-1" class="badge badge-alert" style="display:none"></span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=test%20run">
                            <span>Plant 2</span>
                            <span id="plant-badge-2" class="badge badge-alert" style="display:none"></span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="dashboard_admin.php?plant=packing">
                            <span>Plant 3</span>
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

            <a href="settings.php">
                ⚙️ <span class="text-label">Settings</span>
            </a>

        </div>

        <!-- CONTENT -->

        <div class="content-area" id="contentArea">

            <h3 class="mb-4 text-center">
                <i class="fas fa-industry"></i>
                Machine Monitoring Dashboard - Plant <?= $plant ?>
            </h3>

            <div class="row" id="machine-container">

                <?php while ($row = $result->fetch_assoc()):

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
                                    <input type="hidden" name="plant" value="<?= $plant; ?>">

                                    <div class="form-check form-switch d-flex justify-content-center mb-3">

                                        <input class="form-check-input machine-switch"
                                            type="checkbox"
                                            data-machine-id="<?= $row['id']; ?>"
                                            style="transform:scale(1.5);"
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
        /* SIDEBAR */

        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

        /* MACHINE SWITCH */

        function attachSwitchListeners() {

            document.querySelectorAll('.machine-switch').forEach(switchEl => {

                switchEl.addEventListener('change', function() {

                    const machineId = this.dataset.machineId;
                    const form = this.closest('form');
                    const plant = form.querySelector('input[name="plant"]').value;

                    fetch('update_machine_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `machine_id=${machineId}&plant=${plant}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Status updated');
                            }
                        })
                        .catch(err => console.error(err));

                });

            });

        }

        attachSwitchListeners();


        /* AUTO REFRESH MACHINE */

        function loadMachineData() {

            fetch('get_machine_data.php?plant=<?= $plant ?>')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('machine-container').innerHTML = data;
                    attachSwitchListeners();
                });

        }

        setInterval(loadMachineData, 2000);


        /* PLANT INDICATOR */

        function loadPlantIndicator() {

            fetch("plant_indicator.php")
                .then(response => response.json())
                .then(data => {

                    for (let plant = 1; plant <= 3; plant++) {

                        let badge = document.getElementById("plant-badge-" + plant);

                        if (data[plant] && data[plant] > 0) {
                            badge.innerText = data[plant];
                            badge.style.display = "inline-block";
                        } else {
                            badge.style.display = "none";
                        }

                    }

                });

        }

        setInterval(loadPlantIndicator, 3000);
        loadPlantIndicator();


        /* ======================================
        AUTO SCROLL TV MONITOR STYLE
        ====================================== */

        const content = document.getElementById("contentArea");

        let direction = 1;
        let autoScroll = true;
        let pauseTimer = null;
        let speed = 0.4;

        function autoScrollContent() {

            if (autoScroll) {

                const maxScroll = content.scrollHeight - content.clientHeight;

                content.scrollTop += direction * speed;

                if (content.scrollTop >= maxScroll - 2) {
                    direction = -1;
                }

                if (content.scrollTop <= 2) {
                    direction = 1;
                }

            }

            requestAnimationFrame(autoScrollContent);

        }

        autoScrollContent();


        /* PAUSE WHEN USER TOUCH */

        function pauseAutoScroll() {

            autoScroll = false;

            clearTimeout(pauseTimer);

            pauseTimer = setTimeout(() => {

                autoScroll = true;

            }, 1500);

        }

        content.addEventListener("mousemove", pauseAutoScroll);
        content.addEventListener("touchstart", pauseAutoScroll);
        content.addEventListener("wheel", pauseAutoScroll);
    </script>

</body>

</html>