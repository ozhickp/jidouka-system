<?php
include 'config.php';

$plant = $_GET['plant'];

$stmt = $conn->prepare("
    SELECT form_filled 
    FROM line_mode_logs
    WHERE plant=? AND mode='rework' AND end_time IS NULL
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("s", $plant);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "form_filled" => $result['form_filled'] ?? 0
]);