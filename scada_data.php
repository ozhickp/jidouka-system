<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// ================== MACHINE STATUS ==================
$machines = [];
$q = mysqli_query($conn, "SELECT machine_name, plant, status FROM machine ORDER BY plant ASC, machine_name ASC");

while ($row = mysqli_fetch_assoc($q)) {
    $statusText = ($row['status'] == 1) ? "RUN" : "DOWN";
    $machines[] = [
        'plant' => $row['plant'],
        'machine' => $row['machine_name'],
        'status' => $statusText
    ];
}

// ================== KPI ==================
// Total Downtime (sum of maintenance duration)
$totalDowntimeQ = mysqli_query($conn, "
    SELECT SUM(TIMESTAMPDIFF(MINUTE, waktu_mulai, waktu_selesai)) AS total
    FROM maintenance_logs
");
$totalDowntime = mysqli_fetch_assoc($totalDowntimeQ)['total'] ?? 0;

// Total Breakdown (count of maintenance logs)
$totalBreakdownQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM maintenance_logs
");
$totalBreakdown = mysqli_fetch_assoc($totalBreakdownQ)['total'] ?? 0;

// MTTR
$repairQ = mysqli_query($conn, "
    SELECT SUM(TIMESTAMPDIFF(MINUTE, waktu_mulai, waktu_selesai)) AS repair,
           COUNT(*) AS count_repair
    FROM maintenance_logs
");
$repairRow = mysqli_fetch_assoc($repairQ);
$mttr = ($repairRow['count_repair'] > 0) ? $repairRow['repair'] / $repairRow['count_repair'] : 0;

// MTBF (as 720 min / breakdown count)
$mtbf = ($repairRow['count_repair'] > 0) ? 720 / $repairRow['count_repair'] : 720;

// Availability
$availability = ($mtbf / ($mtbf + ($mttr / 60))) * 100;
$availability = round($availability, 2);

$kpi = [
    'totalDowntime' => $totalDowntime,
    'totalBreakdown' => $totalBreakdown,
    'mttr' => round($mttr, 2),
    'availability' => $availability
];

// ================== Downtime Chart ==================
$chartLabels = [];
$chartData = [];

$chartQ = mysqli_query($conn, "
    SELECT m.machine_name, SUM(TIMESTAMPDIFF(MINUTE, ml.waktu_mulai, ml.waktu_selesai)) AS total_downtime
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    GROUP BY m.machine_name
    ORDER BY m.machine_name ASC
");

while ($row = mysqli_fetch_assoc($chartQ)) {
    $chartLabels[] = $row['machine_name'];
    $chartData[] = $row['total_downtime'] ?? 0;
}

$chart = [
    'labels' => $chartLabels,
    'data' => $chartData
];

// ================== Top Breakdown ==================
$top = [];
$topQ = mysqli_query($conn, "
    SELECT m.machine_name, COUNT(ml.id) AS total
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    GROUP BY m.machine_name
    ORDER BY total DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($topQ)) {
    $top[] = [
        'machine' => $row['machine_name'],
        'total' => $row['total']
    ];
}

// ================== Latest Downtime ==================
$latest = [];
$latestQ = mysqli_query($conn, "
    SELECT m.machine_name, ml.waktu_mulai, ml.waktu_selesai,
           TIMESTAMPDIFF(MINUTE, ml.waktu_mulai, ml.waktu_selesai) AS duration
    FROM maintenance_logs ml
    LEFT JOIN machine m ON m.id = ml.machine_id
    ORDER BY ml.waktu_mulai DESC
    LIMIT 10
");

while ($row = mysqli_fetch_assoc($latestQ)) {
    $latest[] = [
        'machine' => $row['machine_name'],
        'start' => $row['waktu_mulai'],
        'end' => $row['waktu_selesai'],
        'duration' => $row['duration'] ?? 0
    ];
}

// ================== OUTPUT JSON ==================
header('Content-Type: application/json');
echo json_encode([
    'machines' => $machines,
    'kpi' => $kpi,
    'chart' => $chart,
    'top' => $top,
    'latest' => $latest
]);
