<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Monitoring - Plant <?= htmlspecialchars($plant) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #111;
            color: white;
            margin: 0;
            overflow: hidden;
        }

        .monitor-container {
            height: 100vh;
            padding: 20px;
            overflow-y: auto;
        }

        .card-machine {
            border-radius: 12px;
            margin-bottom: 15px;
            transition: 0.3s;
        }

        .card-machine:hover {
            transform: scale(1.03);
        }

        .running {
            background: #28a745;
            color: white;
        }

        .abnormal {
            background: #dc3545;
            color: white;
            animation: blink 1s infinite;
        }

        .maintenance {
            background: #ffc107;
            color: black;
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }

            100% {
                opacity: 1;
            }
        }

        .back-btn {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 999;
        }
    </style>
</head>

<body>

    <a href="dashboard_admin.php?plant=<?= htmlspecialchars($plant) ?>" class="btn btn-light btn-sm back-btn">⬅ Back</a>

    <div class="monitor-container" id="monitorArea">
        <h2 class="text-center mb-4">Machine Monitoring - Plant <?= htmlspecialchars($plant) ?></h2>
        <div class="row" id="machine-container"></div>
    </div>

    <script>
        const container = document.getElementById("monitorArea");
        let direction = 1;
        let speed = 0.5;
        let cycleComplete = false;
        let currentPlant = "<?= addslashes($plant) ?>"; // bisa string
        const plantsList = ["assembly", "test run", "packing"];
        let autoScroll = true;
        let pauseTimer;

        // Fullscreen otomatis
        function goFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            }
        }
        window.addEventListener('load', goFullscreen);

        // Load machine data
        function loadMachine() {
            fetch("get_machine_data.php?plant=" + encodeURIComponent(currentPlant) + "&tv_monitor=1")
                .then(res => res.text())
                .then(data => {
                    document.getElementById("machine-container").innerHTML = data;
                    if (!window.autoScrollStarted) {
                        window.autoScrollStarted = true;
                        requestAnimationFrame(autoScrollFunc);
                    }
                });
        }
        loadMachine();
        setInterval(loadMachine, 3000);

        // Auto scroll
        function autoScrollFunc() {
            if (autoScroll) {
                const maxScroll = container.scrollHeight - container.clientHeight;
                if (maxScroll > 0) {
                    container.scrollTop += direction * speed;

                    if (container.scrollTop >= maxScroll - 2) {
                        direction = -1;
                        cycleComplete = true;
                    }

                    if (container.scrollTop <= 2) {
                        if (cycleComplete) {
                            switchPlant();
                            cycleComplete = false;
                            return;
                        }
                        direction = 1;
                    }
                }
            }
            requestAnimationFrame(autoScrollFunc);
        }

        // Switch plant otomatis
        function switchPlant() {
            let idx = plantsList.indexOf(currentPlant);
            let nextIdx = (idx + 1) % plantsList.length;
            currentPlant = plantsList[nextIdx];
            window.location.href = "view_monitoring.php?plant=" + encodeURIComponent(currentPlant);
        }

        // Pause scroll sementara jika ada aktivitas mouse
        function pauseScroll() {
            autoScroll = false;
            clearTimeout(pauseTimer);
            pauseTimer = setTimeout(() => {
                autoScroll = true;
            }, 1500);
        }

        container.addEventListener('mousemove', pauseScroll);
        container.addEventListener('wheel', pauseScroll);
        container.addEventListener('touchstart', pauseScroll);
    </script>

</body>

</html>