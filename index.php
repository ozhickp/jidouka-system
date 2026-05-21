<?php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MTC Monitoring System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

        <a href="monitoring_public.php">
            <button class="btn btn-main btn-monitor">
                View Monitoring
            </button>
        </a>

        <a href="login_user.php">
            <button class="btn btn-main btn-login">
                Employee Login
            </button>
        </a>

        <!-- <div class="footer">
            Maintenance Tracking Control System
        </div> -->

    </div>

</body>

</html>