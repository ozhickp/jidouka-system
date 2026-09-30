<?php
include 'config.php';
include_once 'conveyor_guard.php';

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';

header('Content-Type: application/json');
echo json_encode([
    "status" => enforce_conveyor_stop($conn, $plant)
]);
