<?php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MTC Monitoring System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            text-align: center;
            width: 90%;
            max-width: 450px;
            animation: fadeIn 0.8s ease;
        }

        .logo {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .title {
            font-size: 26px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 15px;
            color: #666;
            margin-bottom: 30px;
        }

        .btn-main {
            width: 100%;
            padding: 14px;
            font-size: 17px;
            font-weight: 600;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.25s ease;
        }

        .btn-monitor {
            background: #28a745;
            color: white;
            border: none;
        }

        .btn-monitor:hover {
            background: #1e7e34;
            transform: scale(1.04);
        }

        .btn-login {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-login:hover {
            background: #0056b3;
            transform: scale(1.04);
        }

        .btn-back {
            background: #5f0f40;
            color: white;
            border: none;
        }

        .btn-back:hover {
            background: #4a0b31;
            transform: scale(1.04);
        }

        .footer {
            margin-top: 20px;
            font-size: 13px;
            color: #888;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        /* ── POP-UP PILIH MONITORING ── */
        .monitor-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 15px;
        }

        .monitor-overlay.show {
            display: flex;
        }

        .monitor-modal {
            background: white;
            border-radius: 15px;
            padding: 28px 25px 22px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            animation: fadeIn 0.25s ease;
        }

        .monitor-modal h5 {
            font-size: 19px;
            font-weight: 700;
            color: #333;
            margin-bottom: 18px;
        }

        .btn-hde {
            background: #4e73df;
            color: white;
            border: none;
        }

        .btn-hde:hover {
            background: #3a5bc4;
            transform: scale(1.04);
        }

        .btn-picking {
            background: #f6a609;
            color: white;
            border: none;
        }

        .btn-picking:hover {
            background: #d78f06;
            transform: scale(1.04);
        }

        .btn-maint {
            background: #1cc88a;
            color: white;
            border: none;
        }

        .btn-maint:hover {
            background: #14a673;
            transform: scale(1.04);
        }

        .btn-close-modal {
            background: none;
            border: none;
            color: #888;
            font-size: 14px;
            cursor: pointer;
            margin-top: 2px;
        }

        .btn-close-modal:hover {
            color: #444;
        }

        /* responsive */

        @media(max-width:576px) {

            .main-box {
                padding: 30px 20px;
            }

            .title {
                font-size: 22px;
            }

        }
    </style>

</head>

<body>

    <div class="main-box">

        <img src="assets/company_logo.jpg" class="logo">

        <div class="title">
            Monitoring System
        </div>

        <div class="subtitle">
            Machine & Conveyor Monitoring
        </div>

        <button type="button" class="btn btn-main btn-monitor" onclick="openMonitorModal()">
            View Monitoring
        </button>

        <a href="login_user.php">
            <button class="btn btn-main btn-login">
                Employee Login
            </button>
        </a>

        <a href="https://mainhub.yadin.com">
            <button class="btn btn-main btn-back">
                <i class="bi bi-arrow-left"></i> Back to Maintenance Hub
            </button>
        </a>

        <!-- <div class="footer">
            Maintenance Tracking Control System
        </div> -->

    </div>

    <!-- Pop-up pilih jenis monitoring -->
    <div class="monitor-overlay" id="monitorOverlay" onclick="if (event.target === this) closeMonitorModal()">
        <div class="monitor-modal">
            <h5>Pilih Monitoring</h5>

            <a href="monitoring_public.php">
                <button type="button" class="btn btn-main btn-hde">HDE Production</button>
            </a>

            <a href="monitoring_picking_hde.php">
                <button type="button" class="btn btn-main btn-picking">Picking Yard</button>
            </a>

            <a href="monitoring_maintenance.php">
                <button type="button" class="btn btn-main btn-maint">Maintenance</button>
            </a>

            <button type="button" class="btn-close-modal" onclick="closeMonitorModal()">Batal</button>
        </div>
    </div>

    <script>
        function openMonitorModal() {
            document.getElementById('monitorOverlay').classList.add('show');
        }

        function closeMonitorModal() {
            document.getElementById('monitorOverlay').classList.remove('show');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMonitorModal();
        });
    </script>

</body>

</html>