<?php
// Dipanggil otomatis oleh halaman monitoring saat proses sudah RUNNING lagi,
// supaya pesan lama tidak muncul lagi kalau proses itu merah di kemudian hari.
include 'config.php';
header('Content-Type: application/json');

$target  = $_POST['target'] ?? '';
$plant   = strtolower(trim($_POST['plant'] ?? ''));
$machine = trim($_POST['machine_name'] ?? '');

if (!in_array($target, ['picking_yard', 'maintenance'], true) || $plant === '' || $machine === '') {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $conn->prepare("UPDATE machine_notify SET is_active = 0, cleared_at = NOW()
                        WHERE target = ? AND plant = ? AND machine_name = ? AND is_active = 1");
$stmt->bind_param('sss', $target, $plant, $machine);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success']);