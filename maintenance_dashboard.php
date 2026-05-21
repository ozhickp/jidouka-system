<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Fungsi format duration menjadi jam & menit
function formatDuration($minutes)
{
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    $result = '';
    if ($hours > 0) $result .= $hours . ' jam ';
    $result .= $mins . ' menit';
    return $result;
}

// Ambil daftar plant untuk dropdown
$plant_result = mysqli_query($conn, "SELECT DISTINCT plant FROM machine ORDER BY plant ASC");
$plants = [];
while ($p = mysqli_fetch_assoc($plant_result)) {
    $plants[] = $p['plant'];
}

// Ambil plant yang dipilih, default "all"
$selectedPlant = isset($_GET['plant']) ? $_GET['plant'] : 'all';
$wherePlant = $selectedPlant !== 'all' ? "WHERE m.plant = '" . mysqli_real_escape_string($conn, $selectedPlant) . "'" : "";
?>
<!DOCTYPE html>
<html>

<head>
    <title>Maintenance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .card {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .green {
            color: #16a34a;
            font-weight: bold;
        }

        .yellow {
            color: #f59e0b;
            font-weight: bold;
        }

        .red {
            color: #dc2626;
            font-weight: bold;
        }

        table th,
        table td {
            text-align: center;
            vertical-align: middle;
        }

        h3,
        h4 {
            font-weight: bold;
        }

        a.btn-back {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">

        <!-- Header & Back -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Maintenance Performance Dashboard</h3>
            <a href="monitor.php" class="btn btn-secondary btn-back">← Back</a>
        </div>

        <!-- Dropdown Plant -->
        <div class="mb-4">
            <form method="get" class="d-flex align-items-center">
                <label for="plantSelect" class="form-label me-2">Select Plant:</label>
                <select name="plant" id="plantSelect" class="form-select w-auto me-2" onchange="this.form.submit()">
                    <option value="all" <?= $selectedPlant == 'all' ? 'selected' : '' ?>>All Plants</option>
                    <?php foreach ($plants as $p) : ?>
                        <option value="<?= $p ?>" <?= $selectedPlant == $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Total Downtime per Machine -->
        <?php
        $downtime = mysqli_query($conn, "
            SELECT m.machine_name as machine,
                   SUM(TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end)) as total_downtime,
                   COUNT(dl.id) as breakdown
            FROM machine m
            LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
            LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id
            $wherePlant
            GROUP BY m.machine_name
            ORDER BY total_downtime DESC
        ");
        ?>
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h4>Total Downtime per Machine</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Machine</th>
                            <th>Total Downtime</th>
                            <th>Total Breakdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($downtime)) { ?>
                            <tr>
                                <td><?= $row['machine'] ?></td>
                                <td><?= formatDuration($row['total_downtime'] ?? 0) ?></td>
                                <td><?= $row['breakdown'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top 5 Machines Most Breakdown -->
        <?php
        $top = mysqli_query($conn, "
            SELECT m.machine_name as machine, COUNT(dl.id) as total
            FROM machine m
            LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
            LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id
            $wherePlant
            GROUP BY m.machine_name
            ORDER BY total DESC
            LIMIT 5
        ");
        ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4>Top 5 Machines Most Breakdown</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Machine</th>
                            <th>Total Breakdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1;
                        while ($row = mysqli_fetch_assoc($top)) { ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= $row['machine'] ?></td>
                                <td><?= $row['total'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MTTR / Availability -->
        <?php
        $mttr = mysqli_query($conn, "
            SELECT m.machine_name as machine,
                   SUM(TIMESTAMPDIFF(MINUTE, ml.waktu_mulai, ml.waktu_selesai)) as total_repair,
                   COUNT(ml.id) as repair_count
            FROM machine m
            LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
            $wherePlant
            GROUP BY m.machine_name
        ");
        ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Machine Reliability (MTTR / MTBF / Availability)</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Machine</th>
                            <th>MTTR (menit)</th>
                            <th>MTBF (jam)</th>
                            <th>Availability (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($mttr)) {
                            $mttr_value = $row['repair_count'] > 0 ? $row['total_repair'] / $row['repair_count'] : 0;
                            $mtbf = $row['repair_count'] > 0 ? (720 / $row['repair_count']) : 720;
                            $availability = ($mtbf / ($mtbf + ($mttr_value / 60))) * 100;
                            $availability = round($availability, 2);
                        ?>
                            <tr>
                                <td><?= $row['machine'] ?></td>
                                <td><?= round($mttr_value, 2) ?></td>
                                <td><?= round($mtbf, 2) ?></td>
                                <td>
                                    <?php
                                    if ($availability >= 98) echo "<span class='green'>$availability</span>";
                                    elseif ($availability >= 95) echo "<span class='yellow'>$availability</span>";
                                    else echo "<span class='red'>$availability</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Latest Downtime History -->
        <?php
        $history = mysqli_query($conn, "
            SELECT m.machine_name as machine,
                   dl.downtime_start,
                   dl.downtime_end,
                   TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end) as duration
            FROM downtime_logs dl
            LEFT JOIN maintenance_logs ml ON ml.id = dl.maintenance_logs_id
            LEFT JOIN machine m ON m.id = ml.machine_id
            $wherePlant
            ORDER BY dl.downtime_start DESC
            LIMIT 10
        ");
        ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h4>Latest Downtime History</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Machine</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($history)) { ?>
                            <tr>
                                <td><?= $row['machine'] ?></td>
                                <td><?= $row['downtime_start'] ?></td>
                                <td><?= $row['downtime_end'] ?></td>
                                <td>
                                    <?php
                                    if ($row['duration'] > 30) echo "<span class='red'>" . formatDuration($row['duration']) . "</span>";
                                    else echo formatDuration($row['duration']);
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>

</html>