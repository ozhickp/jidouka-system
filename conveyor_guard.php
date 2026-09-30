<?php
/*
 * conveyor_guard.php
 * Aturan: selama ada proses di plant yang BUKAN status 1 (merah=2 / kuning=3),
 * conveyor harus OFF - kecuali plant sedang dalam REWORK MODE (di monitor.php
 * mode rework memang mengizinkan conveyor dinyalakan walau ada proses abnormal).
 *
 * Dipakai oleh: get_conveyor_status.php, dashboard_data.php, set_conveyor.php.
 * Conveyor TIDAK pernah dinyalakan otomatis di sini - START tetap manual.
 */

// Plant yang hardware conveyor-nya sudah terpasang. Tambah 'test run' / 'packing' nanti.
const CONVEYOR_AUTO_STOP_PLANTS = ['assembly'];

function conveyor_id_for_plant($plant)
{
    $p = strtolower(trim($plant));
    if ($p === 'assembly')  return 1;
    if ($p === 'test run')  return 2;
    if ($p === 'packing')   return 3;
    return (int)$plant;
}

function conveyor_guard_applies($plant)
{
    return in_array(strtolower(trim($plant)), CONVEYOR_AUTO_STOP_PLANTS, true);
}

function plant_has_abnormal($conn, $plant)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM machine WHERE plant = ? AND status <> 1");
    $stmt->bind_param("s", $plant);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['total'] > 0;
}

/*
 * ASUMSI: tabel line_mode punya kolom `plant` dan `mode` ('rework'/'production').
 * Cocokkan dengan get_line_mode.php kalau nama kolomnya berbeda.
 * Kalau query gagal, dianggap BUKAN rework (arah aman: conveyor tetap OFF).
 */
function is_rework_mode($conn, $plant)
{
    try {
        $stmt = $conn->prepare("SELECT mode FROM line_mode WHERE plant = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param("s", $plant);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && $row['mode'] === 'rework';
    } catch (Throwable $e) {
        return false;
    }
}

// Boleh menyalakan conveyor sekarang?
function conveyor_start_allowed($conn, $plant)
{
    if (!conveyor_guard_applies($plant)) return true;
    if (!plant_has_abnormal($conn, $plant)) return true;
    return is_rework_mode($conn, $plant);
}

// Baca status conveyor; kalau seharusnya OFF tapi masih 1, koreksi ke 2 di database.
function enforce_conveyor_stop($conn, $plant)
{
    $id = conveyor_id_for_plant($plant);

    $stmt = $conn->prepare("SELECT status FROM conveyor WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $status = $row ? (int)$row['status'] : 1;

    if ($status === 1 && !conveyor_start_allowed($conn, $plant)) {
        $stmt = $conn->prepare("UPDATE conveyor SET status = 2 WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $status = 2;
    }

    return $status;
}
