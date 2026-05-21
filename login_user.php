<?php
session_start();
include_once("config.php");

if (isset($_SESSION['user'])) {
    header("Location: monitor.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = trim($_POST['nomor']);
    $nama  = trim($_POST['nama']);

    $cek_nomor = mysqli_query($conn, "SELECT * FROM user WHERE empl_num='$nomor'");
    if (mysqli_num_rows($cek_nomor) == 0) {
        $message = "Employee number is not found";
    } else {
        $user = mysqli_fetch_assoc($cek_nomor);
        if ($user['name'] !== $nama) {
            $message = "Wrong username";
        } else {
            $_SESSION['user'] = $user;
            header("Location: monitor.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employee Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .card-login {
            width: 90%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background-color: #fff;
            animation: fadeIn 1s ease;
            transition: transform 0.3s ease;
        }

        .card-login:hover {
            transform: translateY(-3px);
        }

        .card-login h2 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }

        .btn-primary {
            background-color: #6c5ce7;
            border: none;
            transition: transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #341f97;
            transform: scale(1.05);
        }

        .btn-group-custom {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-group-custom .btn {
            flex: 1 1 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-group-custom .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-register {
            background-color: #00b894;
            color: #fff;
        }

        .btn-register:hover {
            background-color: #019875;
            color: #fff;
        }

        .btn-admin {
            background-color: #2d3436;
            color: #fff;
        }

        .btn-admin:hover {
            background-color: #1b1b1b;
            color: #fff;
        }

        .btn-back-home {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-back-home:hover {
            background-color: #5a6268;
            color: #fff;
        }

        .logo-img {
            display: block;
            margin: 0 auto 1rem auto;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .alert {
            margin-top: 1rem;
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

        @media (max-width:768px) {
            .card-login {
                padding: 1.5rem;
            }
        }

        @media (max-width:576px) {

            .logo-img {
                width: 60px;
                height: 60px;
            }

            .btn-group-custom {
                gap: 10px;
            }

            .card-login h2 {
                font-size: 1.5rem;
            }

        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="card card-login">

            <img src="assets/company_logo.jpg" alt="Company Logo" class="logo-img">

            <h2>Login</h2>

            <?php if ($message): ?>
                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="nomor" class="form-control" placeholder="Insert Your Employee Number" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="nama" class="form-control" placeholder="Insert Your Name" required>
                </div>

                <button class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

            </form>

            <div class="btn-group-custom mt-3">

                <a href="employee_register.php" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> Register
                </a>

                <a href="login_admin.php" class="btn btn-admin">
                    <i class="fas fa-shield-alt"></i> Admin
                </a>

                <a href="index.php" class="btn btn-back-home">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </div>

        </div>

    </div>

</body>

</html>