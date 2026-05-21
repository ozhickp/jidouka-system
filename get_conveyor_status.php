<?php
include 'config.php';

$plant = $_GET['plant'];

// mapping plant ke id
if ($plant == 'assembly') {
    $id = 1;
} elseif ($plant == 'test run') {
    $id = 2;
} elseif ($plant == 'packing') {
    $id = 3;
} else {
    $id = (int)$plant; // fallback
}

$query = mysqli_query($conn, "SELECT status FROM conveyor WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

echo json_encode([
    "status" => $data['status'] ?? 1
]);
