<?php
// Dipanggil dari monitor.php (user login) untuk mengirim info ke Picking Yard / Maintenance.
// Selalu membalas JSON; semua error ditangkap dan alasannya dikirim ke pop-up.
header('Content-Type: application/json');

function out($arr, $code = 200)
{
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

try {
    session_start();
    include 'config.php';

    // Biar error database dilempar sebagai exception (bisa ditangkap di bawah)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if (!isset($_SESSION['user'])) {
        out(['status' => 'error', 'message' => 'Belum login, silakan login ulang'], 401);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['status' => 'error', 'message' => 'Method tidak diizinkan'], 405);
    }

    $target  = $_POST['target'] ?? '';
    $plant   = strtolower(trim($_POST['plant'] ?? ''));
    $machine = trim($_POST['machine_name'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!in_array($target, ['picking_yard', 'maintenance'], true) || $plant === '' || $machine === '') {
        out(['status' => 'error', 'message' => 'Data tidak lengkap']);
    }
    if ($message === '') {
        out(['status' => 'error', 'message' => 'Pesan tidak boleh kosong']);
    }
    $message = function_exists('mb_substr') ? mb_substr($message, 0, 255) : substr($message, 0, 255);

    $sentBy = $_SESSION['user']['name'] ?? 'Unknown';
    if (!is_string($sentBy)) $sentBy = 'Unknown';

    // Nonaktifkan pesan lama untuk proses & tujuan yang sama (selalu hanya 1 pesan aktif)
    $stmt = $conn->prepare("UPDATE machine_notify SET is_active = 0, cleared_at = NOW()
                            WHERE target = ? AND plant = ? AND machine_name = ? AND is_active = 1");
    $stmt->bind_param('sss', $target, $plant, $machine);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO machine_notify (target, plant, machine_name, message, sent_by)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $target, $plant, $machine, $message, $sentBy);
    $stmt->execute();
    $stmt->close();

    out(['status' => 'success']);
} catch (Throwable $e) {
    out(['status' => 'error', 'message' => 'Error server: ' . $e->getMessage()], 200);
}