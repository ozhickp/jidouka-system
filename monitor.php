<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';
?>

<!DOCTYPE html>
<html>

<head>
    <title>Production Machine Monitoring</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
            position: relative;
            padding-right: 35px;
        }

        .dropdown-menu .dropdown-item:hover {
            background: #57606f;
        }

        .content-area {
            flex: 1;
            padding: 20px;
        }

        .badge-alert {
            background: red;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            min-width: 18px;
            text-align: center;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* BELL */

        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px 12px;
            margin-right: 15px;
        }

        .notification-bell .badge-count {
            position: absolute;
            top: 0;
            right: 2px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            min-width: 16px;
            text-align: center;
            display: none;
        }

        .notification-bell.has-notification i {
            color: #ff6b6b;
        }

        @keyframes bellRing {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(-15deg);
            }

            75% {
                transform: rotate(15deg);
            }
        }

        .notification-bell.ringing {
            animation: bellRing 0.5s ease-in-out;
        }

        /* PANEL */

        .notification-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .notification-panel.show {
            right: 0;
        }

        .notification-header {
            background: #dc3545;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .notification-item {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .no-notification {
            text-align: center;
            color: #999;
            padding: 30px;
        }

        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            display: none;
        }

        .notification-overlay.show {
            display: block;
        }

        .sound-toggle {
            cursor: pointer;
            padding: 8px 12px;
            margin-right: 10px;
        }

        #navbarAlert {
            color: #ffc107;
            font-weight: bold;
            margin-left: 20px;
        }
    </style>
</head>

<body>

    <audio id="alarmSound" preload="auto">
        <source src="assets/beep2.mp3" type="audio/mpeg">
    </audio>

    <div class="notification-overlay" id="notificationOverlay" onclick="closeNotificationPanel()"></div>

    <div class="notification-panel" id="notificationPanel">

        <div class="notification-header">
            <span><i class="fas fa-bell"></i> Machine Alerts</span>
            <button class="btn btn-sm btn-light" onclick="closeNotificationPanel()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="notification-body" id="notificationBody">

            <div class="no-notification">
                <i class="fas fa-check-circle fa-3x text-success"></i>
                <p class="mt-3">All machines are running normally</p>
            </div>

        </div>
    </div>

    <nav class="navbar navbar-dark bg-dark">

        <div class="container-fluid">

            <button class="btn btn-outline-light" onclick="toggleSidebar()">☰</button>

            <span class="navbar-brand">
                Machine Monitoring System
            </span>

            <span id="navbarAlert"></span>

            <div class="d-flex align-items-center">

                <div class="sound-toggle" onclick="toggleSound()">
                    <i class="fas fa-volume-up fa-lg text-white" id="soundIcon"></i>
                </div>

                <div class="notification-bell" id="notificationBell" onclick="toggleNotificationPanel()">
                    <i class="fas fa-bell fa-lg text-white"></i>
                    <span class="badge-count" id="totalAlertCount">0</span>
                </div>

                <span class="text-white me-3"><?= $_SESSION['user']['name']; ?></span>

                <a href="logout_user.php" class="btn btn-danger btn-sm">
                    Logout
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
                        <a class="dropdown-item" href="monitor.php?plant=assembly">
                            <span>Assembly</span>
                            <span id="plant-badge-1" class="badge-alert" style="display:none">0</span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="monitor.php?plant=test%20run">
                            <span>Test Run</span>
                            <span id="plant-badge-2" class="badge-alert" style="display:none">0</span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="monitor.php?plant=packing">
                            <span>Packing</span>
                            <span id="plant-badge-3" class="badge-alert" style="display:none">0</span>
                        </a>
                    </li>

                </ul>
            </div>

            <hr>

            <a href="history_maintenance.php">
                📋 <span class="text-label">History Maintenance</span>
            </a>

            <a href="history_rework.php">
                🔄 <span class="text-label">History Rework</span>
            </a>

            <a href="maintenance_dashboard.php">
                📊 <span class="text-label">Maintenance Dashboard</span>
            </a>

            <hr>

        </div>

        <div class="content-area">

            <h3 class="mb-3 text-center">
                Production Process Monitoring : <?= ucfirst($plant) ?>
            </h3>

            <div class="d-flex justify-content-center align-items-center gap-3 mb-3" style="flex-wrap: nowrap;">


                <button id="reworkBtn" class="btn btn-warning px-4" style="min-width:160px;">
                    REWORK OFF
                </button>

                <button id="onsw" class="btn btn-secondary px-4" style="min-width:160px;" disabled>
                    CONVEYOR OFF
                </button>

            </div>

            <div class="text-center mb-2" id="formReworkContainer" style="display:none;">
                <button id="formReworkBtn" class="btn btn-primary">
                    Form Rework
                </button>
            </div>

            <div id="dashboard"></div>

            <!-- =========================
     MODAL KONFIRMASI REWORK
========================= -->
            <div class="modal fade" id="confirmReworkModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                ⚠ Konfirmasi Rework
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-center">
                            <p>
                                Apakah Anda yakin ingin mengaktifkan <b>REWORK MODE</b>?<br>
                                Semua alarm akan dinonaktifkan.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button id="confirmReworkBtn" class="btn btn-danger">
                                Aktifkan
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal fade" id="confirmFormReworkModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Konfirmasi Form Rework</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-center">
                            <p>Apakah ingin melanjutkan ke Form Rework?</p>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <a href="#" id="goToFormRework" class="btn btn-primary">
                                Lanjut
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let previousAbnormalCount = 0;
        let abnormalMachinesList = [];
        let currentPlant = "<?= $plant ?>";
        let hasInitialLoad = false;
        let soundEnabled = true;
        let reworkMode = false;
        let reworkConfirmed = false;
        let formfilled = 0;

        let navbarAlertIndex = 0;
        let navbarAlertList = [];

        const reworkBtn = document.getElementById("reworkBtn");

        if (reworkBtn) {

            reworkBtn.addEventListener("click", function() {

                // kalau mau ON → tampilkan konfirmasi dulu
                if (!reworkMode) {

                    let modal = new bootstrap.Modal(
                        document.getElementById('confirmReworkModal')
                    );
                    modal.show();

                } else {

                    // CEK dulu sebelum OFF
                    fetch("check_rework_status.php?plant=" + encodeURIComponent(currentPlant))
                        .then(res => res.json())
                        .then(data => {

                            if (data.form_filled == 1) {

                                // baru boleh OFF
                                setReworkMode("production");

                            } else {

                                alert("Form rework harus diisi terlebih dahulu!");

                            }

                        });

                }

            });

        }

        function setReworkMode(mode) {

            fetch("set_line_mode.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `plant=${encodeURIComponent(currentPlant)}&mode=${mode}`
                })
                .then(res => res.json())
                .then(data => {

                    if (data.status === "success") {

                        reworkMode = data.mode === "rework";
                        updateReworkButton();

                    }

                });

        }

        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed") ? "1" : "0");
        }

        // Restore sidebar state on page load
        (function() {
            if (localStorage.getItem("sidebarCollapsed") === "1") {
                document.getElementById("sidebar").classList.add("collapsed");
            }
        })();

        function toggleSound() {

            soundEnabled = !soundEnabled;

            const icon = document.getElementById("soundIcon");

            if (soundEnabled) {
                icon.classList.remove("fa-volume-mute");
                icon.classList.add("fa-volume-up");
            } else {
                icon.classList.remove("fa-volume-up");
                icon.classList.add("fa-volume-mute");
            }

        }

        function loadDashboard() {
            $("#dashboard").load("dashboard_data.php?plant=" + encodeURIComponent(currentPlant));
        }

        function loadPlantIndicator() {

            fetch("plant_indicator.php")
                .then(response => response.json())
                .then(data => {

                    let totalAbnormal = 0;

                    for (let plant = 1; plant <= 3; plant++) {

                        let badge = document.getElementById("plant-badge-" + plant);

                        if (data[plant] && data[plant] > 0) {

                            badge.innerText = data[plant];
                            badge.style.display = "inline-block";
                            totalAbnormal += data[plant];

                        } else {

                            badge.style.display = "none";

                        }

                    }

                    const bellBadge = document.getElementById("totalAlertCount");
                    const bell = document.getElementById("notificationBell");

                    if (totalAbnormal > 0) {

                        bellBadge.textContent = totalAbnormal;
                        bellBadge.style.display = "inline-block";
                        bell.classList.add("has-notification");

                    } else {

                        bellBadge.style.display = "none";
                        bell.classList.remove("has-notification");

                    }

                    /* AMBIL SEMUA MESIN ABNORMAL */

                    Promise.all([
                            fetch("get_machine_data.php?plant=assembly&format=json").then(r => r.json()),
                            fetch("get_machine_data.php?plant=test%20run&format=json").then(r => r.json()),
                            fetch("get_machine_data.php?plant=packing&format=json").then(r => r.json())
                        ])
                        .then(results => {

                            abnormalMachinesList = [];

                            results.forEach(list => {

                                if (Array.isArray(list)) {

                                    list.forEach(machine => {

                                        if (parseInt(machine.status) === 2) {
                                            abnormalMachinesList.push(machine);
                                        }

                                    });

                                }

                            });

                            updateNotificationPanel();
                            updateNavbarAlert();
                            machineDataReady = true;
                            updateConveyorButton();

                        });

                    if (hasInitialLoad && totalAbnormal > previousAbnormalCount && previousAbnormalCount > 0) {

                        playAlarm();
                        showNotificationPanel();

                    }

                    if (!hasInitialLoad) {
                        hasInitialLoad = true;
                    }

                    previousAbnormalCount = totalAbnormal;

                });

        }

        function loadLineMode() {

            fetch("get_line_mode.php?plant=" + encodeURIComponent(currentPlant))
                .then(res => res.json())
                .then(data => {

                    reworkMode = data.mode === "rework";
                    formFilled = data.form_filled || 0;

                    updateReworkButton();

                });

        }

        function updateReworkButton() {

            const btn = document.getElementById("reworkBtn");
            const formContainer = document.getElementById("formReworkContainer");

            if (reworkMode) {

                btn.classList.remove("btn-warning");
                btn.classList.add("btn-success");

                // ✅ TAMPILKAN FORM BUTTON
                if (formContainer) {
                    formContainer.style.display = "block";
                }

                if (formFilled == 0) {
                    btn.innerText = "REWORK ON (FILL THE FORM)";
                    btn.disabled = true;
                } else {
                    btn.innerText = "REWORK ON (CLICK TO OFF)";
                    btn.disabled = false;
                    if (formContainer) {
                        formContainer.style.display = "none";
                    }
                }

            } else {

                btn.innerText = "REWORK OFF";
                btn.classList.remove("btn-danger");
                btn.classList.add("btn-warning");
                btn.disabled = false;
            }
        }

        function updateNotificationPanel() {

            const body = document.getElementById("notificationBody");

            if (abnormalMachinesList.length == 0) {

                body.innerHTML = `
    <div class="no-notification">
    <i class="fas fa-check-circle fa-3x text-success"></i>
    <p class="mt-3">All machines are running normally</p>
    </div>
    `;

                return;

            }

            let html = '';

            abnormalMachinesList.forEach(machine => {

                html += `
    <div class="notification-item">
    <div><b>${machine.machine_name}</b></div>
    <div>Plant : ${machine.plant}</div>
    <div>Status : <span class="text-danger fw-bold">STOPPED</span></div>
    </div>
    `;

            });

            body.innerHTML = html;

        }

        function updateNavbarAlert() {

            const navbar = document.getElementById("navbarAlert");

            if (!abnormalMachinesList || abnormalMachinesList.length === 0) {

                navbar.innerHTML = "";
                return;

            }

            if (navbarAlertIndex >= abnormalMachinesList.length) {
                navbarAlertIndex = 0;
            }

            let machine = abnormalMachinesList[navbarAlertIndex];

            navbar.innerHTML =
                "⚠ " + machine.machine_name +
                " - Plant " + machine.plant +
                " STOPPED";

        }

        // setInterval(function() {

        //     if (!abnormalMachinesList || abnormalMachinesList.length === 0) return;

        //     navbarAlertIndex++;

        //     if (navbarAlertIndex >= abnormalMachinesList.length) {
        //         navbarAlertIndex = 0;
        //     }

        //     updateNavbarAlert();

        // }, 2500);

        function playAlarm() {

            if (reworkMode) return;

            if (!soundEnabled) return;

            const audio = document.getElementById("alarmSound");

            audio.currentTime = 0;

            audio.play().catch(() => {});

        }

        function toggleNotificationPanel() {

            const panel = document.getElementById("notificationPanel");
            const overlay = document.getElementById("notificationOverlay");

            panel.classList.toggle("show");
            overlay.classList.toggle("show");

        }

        function closeNotificationPanel() {

            document.getElementById("notificationPanel").classList.remove("show");
            document.getElementById("notificationOverlay").classList.remove("show");

        }

        function showNotificationPanel() {

            document.getElementById("notificationPanel").classList.add("show");
            document.getElementById("notificationOverlay").classList.add("show");

            const bell = document.getElementById("notificationBell");

            bell.classList.add("ringing");

            setTimeout(() => bell.classList.remove("ringing"), 500);

        }

        setInterval(function() {

            loadLineMode();

            let modal = document.getElementById("confirmMaintenanceModal");

            if (modal) {

                let modalInstance = bootstrap.Modal.getInstance(modal);

                if (modalInstance && modal.classList.contains("show")) {
                    loadPlantIndicator();
                    return;
                }

            }

            loadConveyorStatus();

            loadDashboard();
            loadPlantIndicator();

        }, 2500);

        setInterval(function() {

            if (!abnormalMachinesList || abnormalMachinesList.length === 0) return;

            navbarAlertIndex++;

            if (navbarAlertIndex >= abnormalMachinesList.length) {
                navbarAlertIndex = 0;
            }

            updateNavbarAlert();

        }, 1500);

        loadDashboard();
        loadPlantIndicator();
        loadConveyorStatus();

        document.getElementById("confirmReworkBtn").addEventListener("click", function() {

            setReworkMode("rework");

            reworkConfirmed = true;
            updateReworkButton();

            let modalEl = document.getElementById('confirmReworkModal');
            let modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

        });

        const formReworkBtn = document.getElementById("formReworkBtn");

        if (formReworkBtn) {
            formReworkBtn.addEventListener("click", function() {

                document.getElementById("goToFormRework").href =
                    "form_rework.php?plant=" + encodeURIComponent(currentPlant);

                let modal = new bootstrap.Modal(
                    document.getElementById('confirmFormReworkModal')
                );
                modal.show();

            });
        }

        // =======================
        // CONVEYOR CONTROL
        // =======================

        let conveyorStatus = 1; // default running

        const conveyorBtn = document.getElementById("onsw");

        // ── Cek apakah ada mesin abnormal di plant saat ini ──
        function hasAbnormalMachine() {
            return abnormalMachinesList.some(m =>
                m.plant.toLowerCase() === currentPlant.toLowerCase()
            );
        }

        // ── Update tampilan & status tombol conveyor ──
        function updateConveyorButton() {

            if (!conveyorBtn) return;

            const isAbnormal = hasAbnormalMachine();

            if (isAbnormal && !reworkMode) {
                // Ada mesin abnormal & bukan mode rework → paksa stop & kunci tombol
                conveyorStatus = 2;
                conveyorBtn.innerText = "CONVEYOR OFF";
                conveyorBtn.classList.remove("btn-danger", "btn-success");
                conveyorBtn.classList.add("btn-secondary");
                conveyorBtn.disabled = true;
                conveyorBtn.title = "Conveyor dikunci: ada mesin stopped";

                // Auto-stop ke database
                fetch("set_conveyor.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `plant=${encodeURIComponent(currentPlant)}&status=2`
                });

            } else {
                // Normal / rework → tombol bisa diklik
                conveyorBtn.disabled = false;
                conveyorBtn.title = "";

                if (conveyorStatus == 2) {
                    conveyorBtn.innerText = "CONVEYOR ON";
                    conveyorBtn.classList.remove("btn-danger", "btn-secondary");
                    conveyorBtn.classList.add("btn-success");
                } else {
                    conveyorBtn.innerText = "CONVEYOR OFF";
                    conveyorBtn.classList.remove("btn-success", "btn-secondary");
                    conveyorBtn.classList.add("btn-danger");
                }
            }
        }

        // ── Klik tombol conveyor ──
        if (conveyorBtn) {
            conveyorBtn.addEventListener("click", function() {

                // Double-check: jika ada abnormal & bukan rework, abaikan klik
                if (hasAbnormalMachine() && !reworkMode) return;

                let newStatus = (conveyorStatus == 1) ? 2 : 1;

                fetch("set_conveyor.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `plant=${encodeURIComponent(currentPlant)}&status=${newStatus}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === "success") {
                            conveyorStatus = newStatus;
                            updateConveyorButton();
                        } else {
                            alert("Gagal update conveyor");
                        }
                    });
            });
        }

        // ── Flag: apakah data mesin sudah siap ──
        let machineDataReady = false;

        // ── Ambil status conveyor dari database ──
        function loadConveyorStatus() {
            fetch("get_conveyor_status.php?plant=" + encodeURIComponent(currentPlant))
                .then(res => res.json())
                .then(data => {
                    conveyorStatus = parseInt(data.status) || 1;
                    // Hanya update tombol jika data mesin sudah siap
                    if (machineDataReady) updateConveyorButton();
                });
        }
    </script>

</body>

</html>