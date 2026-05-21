<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$plant = $_POST['plant'] ?? '';
$mode = $_POST['mode'] ?? '';

if (empty($plant) || empty($mode)) {
    echo json_encode(["status" => "error", "message" => "Plant atau mode tidak boleh kosong"]);
    exit;
}

/* =========================
   SWITCH MODE
=========================*/
$user_id = $_SESSION['user']['id'];

if ($mode === "rework") {
    // Insert log baru
    $stmt = $conn->prepare("
        INSERT INTO line_mode_logs (plant, mode, start_time, created_by, created_at)
        VALUES (?, 'rework', NOW(), ?, NOW())
    ");
    $stmt->bind_param("si", $plant, $user_id);
    $stmt->execute();
} elseif ($mode === "production") {
    // Update end_time untuk log terakhir
    $stmt = $conn->prepare("
        UPDATE line_mode_logs
        SET end_time = NOW(), mode='production'
        WHERE plant=? AND mode='rework' AND end_time IS NULL
    ");
    $stmt->bind_param("s", $plant);
    $stmt->execute();
}

echo json_encode(["status" => "success", "mode" => $mode]);
