<?php
// Dipakai halaman monitoring_picking_hde.php & monitoring_maintenance.php (publik, read-only).
include 'config.php';
header('Content-Type: application/json');

$target = $_GET['target'] ?? '';
if (!in_array($target, ['picking_yard', 'maintenance'], true)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT plant, machine_name, message, sent_by, sent_at
                        FROM machine_notify WHERE target = ? AND is_active = 1");
$stmt->bind_param('s', $target);
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

echo json_encode($rows);