<?php
include 'config.php';

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

$plant_id = 1; // default

if ($plant == "assembly") {
    $plant_id = 1;
} elseif ($plant == "test run") {
    $plant_id = 2;
} elseif ($plant == "packing") {
    $plant_id = 3;
}

$stmt = $conn->prepare("
    SELECT status 
    FROM conveyor 
    WHERE id=?
");
$stmt->bind_param("i", $plant_id);
$stmt->execute();
$conveyor = $stmt->get_result()->fetch_assoc();

/* =========================
   TAMPILKAN STATUS CONVEYOR
=========================*/
if ($conveyor && $conveyor['status'] == 1) {
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

while ($row = $result->fetch_assoc()) {

    $color = "success";
    $statusText = "RUNNING";
    $button = "";

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
    <div class='col-md-3'>
        <div class='card text-center shadow mb-4 border-$color'>
            <div class='card-body'>
                <h5>" . htmlspecialchars($row['machine_name']) . "</h5>
                <span class='badge bg-$color fs-6'>
                    $statusText
                </span>
                <div>$button</div>
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