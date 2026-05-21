<?php
include 'config.php';

header("Content-Type: application/json");

$plant  = $_POST['plant']  ?? '';
$status = (int)($_POST['status'] ?? 1);

// Validasi status: hanya 1 (running) atau 2 (stopped)
if (!in_array($status, [1, 2])) {
    echo json_encode(["status" => "error", "message" => "Status tidak valid"]);
    exit;
}

// Mapping plant ke id
if ($plant == 'assembly') {
    $id = 1;
} elseif ($plant == 'test run') {
    $id = 2;
} elseif ($plant == 'packing') {
    $id = 3;
} else {
    echo json_encode(["status" => "error", "message" => "Plant tidak ditemukan"]);
    exit;
}

// Prepared statement untuk keamanan
$stmt = $conn->prepare("UPDATE conveyor SET status=? WHERE id=?");
$stmt->bind_param("ii", $status, $id);
$result = $stmt->execute();

if ($result) {
    echo json_encode(["status" => "success", "conveyor_status" => $status]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal update database"]);
}
