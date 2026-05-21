<?php
include_once("config.php");

header("Content-Type: application/json");

$plant = $_GET['plant'] ?? '';

$stmt = $conn->prepare("
    SELECT mode, form_filled
    FROM line_mode_logs
    WHERE plant = ? AND end_time IS NULL
    ORDER BY id DESC
    LIMIT 1
");

$stmt->bind_param("s", $plant);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    "mode" => $data['mode'] ?? 'production',
    "form_filled" => $data['form_filled'] ?? 0
]);
