<?php

include "config.php";

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'ALL';

echo "<div class='machine_grid'>";

if ($plant == "ALL") {
    $query_machine = mysqli_query($conn, "SELECT * FROM machine");
} else {
    $stmt = $conn->prepare("SELECT * FROM machine WHERE plant=?");
    $stmt->bind_param("s", $plant);
    $stmt->execute();
    $query_machine = $stmt->get_result();
}

while ($row = mysqli_fetch_assoc($query_machine)) {

    $machine_id = $row['id'];
    $machine    = $row['machine_name'];
    $status     = $row['status'];

    /* STATUS MACHINE */
    if ($status == 1) {
        $condition = "RUNNING";
        $color     = "green";
    } else {
        $condition = "STOPPED";
        $color     = "red";
    }

    /* WAKTU ABNORMAL — hitung selisih detik di server agar tidak ada masalah timezone */
    $abnormal_time_html = "";
    if ($status != 1) {
        $stmt2 = $conn->prepare("
            SELECT TIMESTAMPDIFF(SECOND, downtime_start, NOW()) AS elapsed_sec
            FROM downtime_logs 
            WHERE machine_id = ? AND downtime_end IS NULL 
            ORDER BY downtime_start DESC 
            LIMIT 1
        ");
        $stmt2->bind_param("i", $machine_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result()->fetch_assoc();

        if ($res2 && $res2['elapsed_sec'] >= 0) {
            $elapsed = (int)$res2['elapsed_sec'];
            // Kirim elapsed detik ke JS, bukan epoch timestamp
            $abnormal_time_html = "<div class='abnormal_time' data-since='{$elapsed}'>00:00:00</div>";
        }
    }

    /* STATUS MAINTENANCE */
    $maintenance_text  = "Clear";
    $maintenance_color = "green";

    if (isset($row['repair_status'])) {
        if ($row['repair_status'] == "pending") {
            $maintenance_text  = "Belum Ditangani";
            $maintenance_color = "red";
        } elseif ($row['repair_status'] == "progress") {
            $maintenance_text  = "Sedang Ditangani";
            $maintenance_color = "orange";
        } elseif ($row['repair_status'] == "done") {
            $maintenance_text  = "Clear";
            $maintenance_color = "green";
        }
    }

    echo "
    <div class='machine_card' style='background:white;color:black;'>

        <div style='font-size:20px;font-weight:bold'>
            $machine
        </div>

        <div style='margin-top:10px;color:$color;font-weight:bold'>
            $condition
        </div>

        $abnormal_time_html

        <div class='maintenance_status' style='color:$maintenance_color'>
            $maintenance_text
        </div>

    </div>
    ";
}

echo "</div>";
