<?php
session_start();
include_once("config.php");

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username and Password must be filled!";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $username;
                header("Location: dashboard_admin.php");
                exit();
            } else {
                $error = "Wrong Password!";
            }
        } else {
            $error = "Username not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: #fff;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 1s ease;
        }

        .login-box h2 {
            margin-bottom: 25px;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            font-weight: bold;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .btn-login {
            width: 100%;
            padding: 10px;
            background: #4e73df;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s ease;
        }

        .btn-login:hover {
            background: #2e59d9;
            transform: scale(1.03);
        }

        .btn-back {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: #6c757d;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            background: #5a6268;
            color: #fff;
            text-decoration: none;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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

        @media(max-width:576px) {
            .login-box {
                padding: 1.5rem;
            }

            .btn-login,
            .btn-back {
                font-size: 14px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h2>Admin Login</h2>

        <?php if (!empty($error)) : ?>
            <div class="error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" name="login" class="btn-login">Login</button>
        </form>

        <a href="login_user.php" class="btn-back">Back to Home</a>
    </div>

</body>

</html>