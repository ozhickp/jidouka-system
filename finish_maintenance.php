<?php
include 'config.php';

$machine_id = $_POST['machine_id'];
$kerusakan = $_POST['kerusakan'];
$perbaikan = $_POST['perbaikan'];

$conn->query("INSERT INTO maintenance_logs
(machine_id, kerusakan, perbaikan, waktu_mulai, waktu_selesai)
VALUES ($machine_id, '$kerusakan', '$perbaikan', NOW(), NOW())");

$conn->query("UPDATE machines SET status=1 WHERE id=$machine_id");

$check = $conn->query("SELECT * FROM machines WHERE status=2");

if($check->num_rows == 0){
    $conn->query("UPDATE conveyor SET status=1 WHERE id=1");
}

echo "OK";
?>