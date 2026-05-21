<?php
include 'config.php';

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, engine_name FROM engine WHERE engine_name LIKE CONCAT('%', ?, '%') LIMIT 10");
$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result(); 

$engines = [];
while ($row = $result->fetch_assoc()) {
    $engines[] = $row;
}

header('Content-Type: application/json');
echo json_encode($engines);