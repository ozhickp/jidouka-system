<?php
include 'config.php';
include_once 'conveyor_guard.php';

/* =========================
   AMBIL PLANT DARI URL
=========================*/
$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';

/* =========================
   CEK MESIN ABNORMAL DI PLANT INI
=========================*/
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM machine 
    WHERE plant=? AND status=2
");
$stmt->bind_param("s", $plant);
$stmt->execute();
$cekAbnormal = $stmt->get_result()->fetch_assoc();

/* =========================
   AMBIL STATUS CONVEYOR
   (status dikontrol manual via tombol, tidak di-override otomatis)
=========================*/

// Status efektif: otomatis OFF kalau ada proses merah/kuning (lihat conveyor_guard.php)
$conveyor_status = enforce_conveyor_stop($conn, $plant);

/* =========================
   TAMPILKAN STATUS CONVEYOR
=========================*/
if ($conveyor_status == 1) {
    echo "
    <div class='alert alert-success text-center'>
        🟢 Conveyor Plant $plant RUNNING
    </div>";
} else {
    echo "
    <div class='alert alert-danger text-center'>
        🔴 Conveyor Plant $plant STOPPED
    </div>";
}

echo "<div class='row'>";

/* =========================
   AMBIL DATA MESIN (TERMASUK PLANT)
=========================*/
$stmt = $conn->prepare("
    SELECT id, machine_name, status, plant
    FROM machine 
    WHERE plant=?
");
$stmt->bind_param("s", $plant);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);

/* Line dianggap bermasalah kalau ada 1 saja proses yang bukan RUNNING
   (status 2 = merah/stopped, status 3 = kuning/maintenance).
   Saat itu, proses yang normal ditampilkan OFF (mati), bukan hijau -
   sama seperti pilot lamp fisik di line. */
$line_has_problem = false;
foreach ($rows as $r) {
    if ($r['status'] != 1) {
        $line_has_problem = true;
        break;
    }
}

foreach ($rows as $row) {

    $color = "success";
    $statusText = "RUNNING";
    $button = "";

    /* PROSES NORMAL TAPI LINE SEDANG ADA MASALAH -> OFF */
    if ($row['status'] == 1 && $line_has_problem) {
        $color = "secondary";
        $statusText = "OFF";
    }

    /* STATUS ABNORMAL */
    if ($row['status'] == 2) {
        $color = "danger";
        $statusText = "STOPPED";

        $button = "
        <button 
            class='btn btn-warning btn-sm mt-2'
            onclick=\"confirmMaintenance(
                " . $row['id'] . ",
                '" . addslashes($row['machine_name']) . "',
                '" . addslashes($row['plant']) . "'
            )\">
            Konfirmasi Perbaikan
        </button>
        ";
    }

    /* STATUS MAINTENANCE */
    if ($row['status'] == 3) {
        $color = "warning";
        $statusText = "MAINTENANCE";
    }

    echo "
    <div class='col-md-3 mb-4'>
        <div class='card machine-card text-center shadow h-100 border-$color'>
            <div class='card-body'>
                <h5 class='machine-name'>" . htmlspecialchars($row['machine_name']) . "</h5>
                <span class='badge bg-$color fs-6'>
                    $statusText
                </span>
                <div class='machine-action'>$button</div>
            </div>
        </div>
    </div>
    ";
}

echo "</div>";
?>

<!-- =========================
     MODAL KONFIRMASI MAINTENANCE
========================= -->
<div class="modal fade" id="confirmMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-tools"></i>
                    Konfirmasi Perbaikan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p id="confirmText">
                    Apakah ingin melanjutkan maintenance?
                </p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="#" id="confirmBtn" class="btn btn-warning">
                    Lanjut
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    /* =========================
   POPUP KONFIRMASI
=========================*/
    function confirmMaintenance(machineId, machineName, plant) {

        document.getElementById("confirmText").innerHTML =
            "Apakah ingin melakukan maintenance pada <b>" + machineName + "</b><br>" +
            "Plant: <b>" + plant + "</b>?";

        document.getElementById("confirmBtn").href =
            "form_maintenance.php?machine_id=" + machineId;

        var modal = new bootstrap.Modal(
            document.getElementById('confirmMaintenanceModal')
        );
        modal.show();
    }
</script>