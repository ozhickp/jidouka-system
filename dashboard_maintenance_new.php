<?php
header('Content-Type: application/json');
include 'config.php';

// Ambil plant dari query, default "all"
$plant = isset($_GET['plant']) ? $_GET['plant'] : 'all';
$wherePlant = $plant !== 'all' ? "WHERE m.plant='" . mysqli_real_escape_string($conn, $plant) . "'" : "";

// ===== Total Downtime per Machine =====
$queryDowntime = "
    SELECT m.machine_name, m.plant,
           SUM(TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end)) as total_downtime
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id
    $wherePlant
    GROUP BY m.id
    ORDER BY m.plant ASC, CAST(SUBSTRING_INDEX(m.machine_name,' ', -1) AS UNSIGNED)
";
$res = mysqli_query($conn, $queryDowntime);
$machines = $downtimes = [];
while ($row = mysqli_fetch_assoc($res)) {
    $machines[] = $row['machine_name'];
    $downtimes[] = (int)$row['total_downtime'];
}

// ===== Top 5 Machines Most Breakdown =====
$queryTop = "
    SELECT m.machine_name, COUNT(dl.id) as breakdown
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id
    $wherePlant
    GROUP BY m.id
    ORDER BY breakdown DESC
    LIMIT 5
";
$resTop = mysqli_query($conn, $queryTop);
$top_labels = $top_data = [];
while ($row = mysqli_fetch_assoc($resTop)) {
    $top_labels[] = $row['machine_name'];
    $top_data[] = (int)$row['breakdown'];
}

// ===== MTTR & Availability =====
$queryMTTR = "
    SELECT m.machine_name,
           SUM(TIMESTAMPDIFF(MINUTE, ml.waktu_mulai, ml.waktu_selesai)) as total_repair,
           COUNT(ml.id) as repair_count
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    $wherePlant
    GROUP BY m.id
    ORDER BY m.plant ASC, CAST(SUBSTRING_INDEX(m.machine_name,' ', -1) AS UNSIGNED)
";
$resMTTR = mysqli_query($conn, $queryMTTR);
$mttr_labels = $mttr_values = $availability_values = [];
while ($row = mysqli_fetch_assoc($resMTTR)) {
    $mttr_val = $row['repair_count'] > 0 ? $row['total_repair'] / $row['repair_count'] : 0;
    $mtbf = $row['repair_count'] > 0 ? 720 / $row['repair_count'] : 720;
    $availability = round(($mtbf / ($mtbf + ($mttr_val / 60))) * 100, 2);

    $mttr_labels[] = $row['machine_name'];
    $mttr_values[] = round($mttr_val, 2);
    $availability_values[] = $availability;
}

// ===== Latest Downtime =====
$queryLatest = "
    SELECT m.machine_name, TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end) as duration
    FROM downtime_logs dl
    LEFT JOIN maintenance_logs ml ON ml.id = dl.maintenance_logs_id
    LEFT JOIN machine m ON m.id = ml.machine_id
    $wherePlant
    ORDER BY dl.downtime_start DESC
    LIMIT 10
";
$resLatest = mysqli_query($conn, $queryLatest);
$latest_machines = $latest_durations = [];
while ($row = mysqli_fetch_assoc($resLatest)) {
    $latest_machines[] = $row['machine_name'];
    $latest_durations[] = (int)$row['duration'];
}

// Kembalikan JSON
echo json_encode([
    "machines" => $machines,
    "downtimes" => $downtimes,
    "top_labels" => $top_labels,
    "top_data" => $top_data,
    "mttr_labels" => $mttr_labels,
    "mttr_values" => $mttr_values,
    "availability_values" => $availability_values,
    "latest_machines" => $latest_machines,
    "latest_durations" => $latest_durations
]);
