<?php
include_once("config.php");

$error = "";
$success = "";

if (isset($_POST['submit'])) {

    $nama   = trim($_POST['nama']);
    $nomor  = trim($_POST['nomor']);
    $divisi = $_POST['divisi'];

    if (empty($nama) || empty($nomor) || $divisi == "#") {
        $error = "Semua field wajib diisi!";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $nama)) {
        $error = "Name can't include number!";
    } elseif (!ctype_digit($nomor)) {
        $error = "Employee Number must be number!";
    } elseif (strlen($nomor) > 10) {
        $error = "Employee Number is 10 digit maximum!";
    } else {

        $cek = mysqli_query(
            $conn,
            "SELECT * FROM user WHERE empl_num='$nomor'"
        );

        if (mysqli_num_rows($cek) > 0) {
            $error = "Employee number is registered!";
        } else {

            mysqli_query(
                $conn,
                "INSERT INTO user(name,empl_num,division,created_at)
            VALUES('$nama','$nomor','$divisi',NOW())"
            );

            $success = "Register Successful!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card-register {
            width: 90%;
            max-width: 450px;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            background-color: #fff;
            animation: fadeIn 1s ease;
        }

        .card-register h3 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
        }

        .btn-success {
            border-radius: 30px;
            transition: transform 0.2s ease;
        }

        .btn-success:hover {
            transform: scale(1.05);
        }

        .alert {
            margin-bottom: 1rem;
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

        @media(max-width: 576px) {
            .card-register {
                padding: 1.5rem;
            }

            .btn-success {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="card card-register">
        <h3>Employee Register</h3>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)) : ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="nama" class="form-control" placeholder="Insert your name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Employee Number</label>
                <input type="number"
                    name="nomor"
                    class="form-control"
                    placeholder="Insert your employee number"
                    maxlength="10"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                    required>
            </div>

            <div class="mb-4">
                <label class="form-label">Division</label>
                <select name="divisi" class="form-select" required>
                    <option value="#">-- Choose Division --</option>
                    <option value="Production">Production</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Assembly">Assembly</option>
                </select>
            </div>

            <button type="submit" name="submit" class="btn btn-success w-100 mb-3">Register</button>
        </form>

        <div class="text-center">
            <a href="index.php" class="btn btn-outline-dark btn-sm">Back to Home</a>
        </div>
    </div>

</body>

</html>