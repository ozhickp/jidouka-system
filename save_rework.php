<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$plant = $_POST['plant'] ?? '';
$engine_id = $_POST['engine_id'] ?? '';
$engine_serial = $_POST['engine_serial'] ?? '';
$action = $_POST['corrective_action'] ?? '';
$user_id = $_SESSION['user']['id'];

if (empty($plant) || empty($engine_id) || empty($engine_serial) || empty($action)) {
    echo json_encode(["status" => "error", "message" => "Semua field wajib diisi"]);
    exit;
}

/* =========================
   UPDATE LOG REWORK TERAKHIR
   (SET engine_id, serial, action, end_time, user)
=========================*/
$stmt = $conn->prepare("
    UPDATE line_mode_logs
    SET engine_id=?, engine_serial=?, corrective_action=?, form_filled=1
    WHERE plant=? AND mode='rework' AND end_time IS NULL
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("isss", $engine_id, $engine_serial, $action, $plant);

if ($stmt->execute()) {
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Gagal menyimpan"]);
}
?>