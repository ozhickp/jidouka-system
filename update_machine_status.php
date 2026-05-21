<?php
session_start();
include 'config.php';
include 'send_notification.php';

if (!isset($_SESSION['admin_id'])) {
    exit;
}

if (!isset($_POST['machine_id']) || !isset($_POST['plant'])) {
    exit;
}

$machine_id = (int)$_POST['machine_id'];
$plant      = $_POST['plant'];

// ==========================
// Ambil status lama mesin
// ==========================
$stmt = $conn->prepare("SELECT status, machine_name FROM machine WHERE id=?");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard_admin.php?plant=" . $plant);
    exit;
}

$row = $result->fetch_assoc();
$old_status   = $row['status'];
$machine_name = $row['machine_name'];


// ==========================
// Update status menjadi ABNORMAL
// ==========================
$stmt = $conn->prepare("
    UPDATE machine 
    SET 
        status = 2,
        repair_status = 'pending',
        last_update = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $machine_id);
$stmt->execute();


// ==========================
// START DOWNTIME
// ==========================
if ($old_status != 2) {

    $stmt2 = $conn->prepare("
        INSERT INTO downtime_logs
        (machine_id, plant, downtime_start)
        VALUES (?, ?, NOW())
    ");
    $stmt2->bind_param("is", $machine_id, $plant);
    $stmt2->execute();

    sendAbnormalNotification($machine_id);
}

header("Location: dashboard_admin.php?plant=" . $plant);
exit;
