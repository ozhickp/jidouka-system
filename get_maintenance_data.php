<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    exit('Unauthorized');
}

function formatDuration($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}

$filterType = $_GET['filterType'] ?? 'daily';
$filterValue = $_GET['filterValue'] ?? date('Y-m-d');

$sql = "
SELECT ml.*, m.machine_name, m.plant, e.engine_name,
       dl.downtime_start, dl.downtime_end
FROM maintenance_logs ml
JOIN machine m ON ml.machine_id = m.id
LEFT JOIN engine e ON ml.engine_id = e.id
LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id
";

$where = [];

if ($filterType === 'daily' && $filterValue) {
    $where[] = "DATE(ml.waktu_mulai)='" . $conn->real_escape_string($filterValue) . "'";
} elseif ($filterType === 'monthly' && $filterValue) {
    $where[] = "DATE_FORMAT(ml.waktu_mulai,'%Y-%m')='" . $conn->real_escape_string($filterValue) . "'";
} elseif ($filterType === 'yearly' && $filterValue) {
    $where[] = "YEAR(ml.waktu_mulai)='" . intval($filterValue) . "'";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY ml.waktu_mulai DESC";

$result = $conn->query($sql);

$no = 1;

$totalDowntimeSeconds = 0;
$totalMaintenanceSeconds = 0;

$html = "";

while ($row = $result->fetch_assoc()) {

    $downtimeDur = '-';
    $maintDur = '-';

    $downtimeSeconds = 0;
    $maintenanceSeconds = 0;

    if ($row['downtime_start'] && $row['downtime_end']) {

        $downtimeSeconds = strtotime($row['downtime_end']) - strtotime($row['downtime_start']);
        $downtimeDur = formatDuration($downtimeSeconds);

        $totalDowntimeSeconds += $downtimeSeconds;
    }

    if ($row['waktu_selesai']) {

        $maintenanceSeconds = strtotime($row['waktu_selesai']) - strtotime($row['waktu_mulai']);
        $maintDur = formatDuration($maintenanceSeconds);

        $totalMaintenanceSeconds += $maintenanceSeconds;
    }

    $html .= "<tr>";

    $html .= "<td>{$no}</td>";
    $html .= "<td class='machine-col'>{$row['machine_name']}</td>";
    $html .= "<td>{$row['plant']}</td>";
    $html .= "<td class='text-limit'>" . ($row['kerusakan_machine'] ?: '-') . "</td>";
    $html .= "<td class='text-limit'>" . ($row['perbaikan_machine'] ?: '-') . "</td>";
    $html .= "<td class='text-limit'>" . ($row['kerusakan_engine'] ?: '-') . "</td>";
    $html .= "<td class='text-limit'>" . ($row['perbaikan_engine'] ?: '-') . "</td>";

    $html .= "<td>";

    if ($row['dokumentasi_machine']) {
        $html .= "<button class='btn btn-sm btn-outline-primary doc-btn view-image'
        data-image='uploads/machine/{$row['dokumentasi_machine']}'>View</button>";
    } else {
        $html .= "-";
    }

    $html .= "</td>";

    $html .= "<td>";

    if ($row['dokumentasi_engine']) {
        $html .= "<button class='btn btn-sm btn-outline-success doc-btn view-image'
        data-image='uploads/engine/{$row['dokumentasi_engine']}'>View</button>";
    } else {
        $html .= "-";
    }

    $html .= "</td>";

    $html .= "<td>{$row['handled_by']}</td>";
    $html .= "<td>{$row['downtime_start']}</td>";
    $html .= "<td>{$row['downtime_end']}</td>";
    $html .= "<td>{$row['waktu_mulai']}</td>";
    $html .= "<td>{$row['waktu_selesai']}</td>";

    $html .= "<td><span class='duration-badge'>{$downtimeDur}</span></td>";
    $html .= "<td><span class='duration-badge'>{$maintDur}</span></td>";

    $html .= "</tr>";

    $no++;
}

$totalDowntime = formatDuration($totalDowntimeSeconds);
$totalMaintenance = formatDuration($totalMaintenanceSeconds);

echo $html;

echo "<script>
document.getElementById('totalDowntime').innerHTML='$totalDowntime';
document.getElementById('totalMaintenance').innerHTML='$totalMaintenance';
</script>";
