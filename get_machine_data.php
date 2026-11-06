<?php
include_once("config.php");

if (!isset($_GET['plant'])) {
    echo "<div class='col-12 text-center text-danger'>Plant tidak ditemukan</div>";
    exit;
}

$plant = $_GET['plant'];
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

$stmt = $conn->prepare("SELECT * FROM machine WHERE plant=? ORDER BY id ASC");
$stmt->bind_param("s", $plant);
$stmt->execute();
$result = $stmt->get_result();

/* ================= LOGIKA LAMPU (SAMA DENGAN PILOT LAMP FISIK) =================
   - Semua proses 'done'           -> semua HIJAU
   - Ada proses belum 'done'       -> hanya proses bermasalah yang menyala:
                                        pending  = MERAH
                                        progress = KUNING
                                      proses lain = OFF (bukan hijau, bukan merah)
*/
$rows = $result->fetch_all(MYSQLI_ASSOC);

$line_has_problem = false;
foreach ($rows as $r) {
    if ($r['repair_status'] !== 'done') {
        $line_has_problem = true;
        break;
    }
}

function lamp_state($repair_status, $line_has_problem)
{
    if (!$line_has_problem)            return 'green';
    if ($repair_status === 'pending')  return 'red';
    if ($repair_status === 'progress') return 'yellow';
    return 'off';
}


/* ================= JSON MODE (UNTUK monitor.php) ================= */

if ($format === "json") {

    $machines = [];

    foreach ($rows as $row) {

        $machines[] = [
            "id" => $row['id'],
            "machine_name" => $row['machine_name'],
            "plant" => $row['plant'],
            "status" => $row['status'],
            "repair_status" => $row['repair_status'],
            "lamp" => lamp_state($row['repair_status'], $line_has_problem),
            "line_has_problem" => $line_has_problem
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($machines);
    exit;
}


/* ================= HTML MODE (UNTUK dashboard_admin.php) ================= */

foreach ($rows as $row) {

    $lamp = lamp_state($row['repair_status'], $line_has_problem);

    if ($lamp === 'off') {
        // Proses normal tapi line sedang ada masalah -> lampu mati
        $status_text = "OFF";
        $badge = "secondary";
    } elseif ($row['status'] == 1) {
        $status_text = "RUNNING";
        $badge = "success";
    } elseif ($row['status'] == 2) {
        $status_text = "STOPPED";
        $badge = "danger";
    } else {
        $status_text = "MAINTENANCE";
        $badge = "warning";
    }

    if ($lamp === 'off') {
        $repair_text = "-";
        $repair_badge = "secondary";
    } elseif ($row['repair_status'] == "pending") {
        $repair_text = "Belum Ditangani";
        $repair_badge = "danger";
    } elseif ($row['repair_status'] == "progress") {
        $repair_text = "Sedang Diperbaiki";
        $repair_badge = "warning";
    } elseif ($row['repair_status'] == "done") {
        $repair_text = "Clear";
        $repair_badge = "success";
    } else {
        $repair_text = "-";
        $repair_badge = "secondary";
    }
?>

    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">

        <div class="card card-machine shadow h-100">

            <div class="card-body text-center">

                <h5 class="machine-name"><?= htmlspecialchars($row['machine_name']); ?></h5>

                <form method="POST" action="update_machine_status.php">

                    <input type="hidden" name="machine_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="plant" value="<?= $plant; ?>">

                    <div class="form-check form-switch d-flex justify-content-center mb-3">

                        <input class="form-check-input machine-switch"
                            type="checkbox"
                            data-machine-id="<?= $row['id']; ?>"
                            style="transform:scale(1.5);"
                            <?= ($row['status'] != 1) ? "checked disabled" : "" ?>>

                    </div>

                </form>

                <span class="badge bg-<?= $badge ?> mb-2">
                    <?= $status_text ?>
                </span>

                <br>

                <span class="badge bg-<?= $repair_badge ?>">
                    <?= $repair_text ?>
                </span>

                <hr>

                <small class="text-muted">Last Update:</small><br>

                <small>
                    <?= $row['last_update']; ?>
                </small>

            </div>

        </div>

    </div>

<?php
}
?>