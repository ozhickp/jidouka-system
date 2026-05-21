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
    <!-- Original CSS here -- exactly as before -->
    <style>
        /* ... original styles ... */
    </style>
</head>

<body>
    <!-- Original login form -->
</body>

</html>
