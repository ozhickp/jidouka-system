<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

function formatDuration($minutes)
{
    $hours = floor($minutes / 60);
    $mins  = $minutes % 60;
    $result = '';
    if ($hours > 0) $result .= $hours . 'j ';
    $result .= $mins . 'm';
    return $result;
}

$plant_result = mysqli_query($conn, "SELECT DISTINCT plant FROM machine ORDER BY plant ASC");
$plants = [];
while ($p = mysqli_fetch_assoc($plant_result)) $plants[] = $p['plant'];

$selectedPlant = isset($_GET['plant']) ? $_GET['plant'] : 'all';
$wherePlant    = $selectedPlant !== 'all'
    ? "WHERE m.plant = '" . mysqli_real_escape_string($conn, $selectedPlant) . "'"
    : "";

// ── Filter bulan & tahun ──
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : 0;
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = 0;
if ($selectedMonth > 0 && $selectedYear === 0) $selectedYear = (int)date('Y'); // pilih bulan tanpa tahun -> tahun ini

// Daftar tahun yang tersedia dari data downtime
$years = [];
$yr = mysqli_query($conn, "SELECT DISTINCT YEAR(downtime_start) AS y FROM downtime_logs WHERE downtime_start IS NOT NULL ORDER BY y DESC");
while ($y = mysqli_fetch_assoc($yr)) $years[] = (int)$y['y'];
if ($selectedYear > 0 && !in_array($selectedYear, $years)) {
    $years[] = $selectedYear;
    rsort($years);
}

// Rentang tanggal [start, end) — nilai sudah int, aman dari injection
$dlRange = '';   // untuk downtime_logs
$mlRange = '';   // untuk maintenance_logs
$periodHours = 720; // default lama (30 hari) saat tidak ada filter periode
if ($selectedYear > 0) {
    $start = $selectedMonth > 0
        ? new DateTime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth))
        : new DateTime(sprintf('%04d-01-01', $selectedYear));
    $end = (clone $start)->modify($selectedMonth > 0 ? '+1 month' : '+1 year');
    $s = $start->format('Y-m-d');
    $e = $end->format('Y-m-d');
    $dlRange = "AND dl.downtime_start >= '$s' AND dl.downtime_start < '$e'";
    $mlRange = "AND ml.waktu_mulai >= '$s' AND ml.waktu_mulai < '$e'";
    $periodHours = (int)$start->diff($end)->days * 24; // jam operasional periode (untuk MTBF)
}
$whereHist = $wherePlant !== ''
    ? $wherePlant . " " . $dlRange
    : ($dlRange !== '' ? "WHERE 1=1 " . $dlRange : "");

// 1. Downtime per machine
$downtime = mysqli_query($conn, "
    SELECT m.machine_name as machine,
           SUM(TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end)) as total_downtime,
           COUNT(dl.id) as breakdown
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id $dlRange
    $wherePlant
    GROUP BY m.machine_name
    ORDER BY total_downtime DESC
");
$dtRows = [];
while ($r = mysqli_fetch_assoc($downtime)) $dtRows[] = $r;

// 2. Top 5
$top = mysqli_query($conn, "
    SELECT m.machine_name as machine, COUNT(dl.id) as total
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id
    LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = ml.id $dlRange
    $wherePlant
    GROUP BY m.machine_name
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 5
");
$topRows = [];
while ($r = mysqli_fetch_assoc($top)) $topRows[] = $r;

// 3. MTTR/MTBF/Availability
$mttr = mysqli_query($conn, "
    SELECT m.machine_name as machine,
           SUM(TIMESTAMPDIFF(MINUTE, ml.waktu_mulai, ml.waktu_selesai)) as total_repair,
           COUNT(ml.id) as repair_count
    FROM machine m
    LEFT JOIN maintenance_logs ml ON ml.machine_id = m.id $mlRange
    $wherePlant
    GROUP BY m.machine_name
");
$mttrRows = [];
while ($r = mysqli_fetch_assoc($mttr)) {
    $mv = $r['repair_count'] > 0 ? $r['total_repair'] / $r['repair_count'] : 0;
    $mb = $r['repair_count'] > 0 ? ($periodHours / $r['repair_count']) : $periodHours;
    $av = round(($mb / ($mb + ($mv / 60))) * 100, 2);
    $mttrRows[] = [
        'machine'      => $r['machine'],
        'mttr'         => round($mv, 2),
        'mtbf'         => round($mb, 2),
        'availability' => $av,
    ];
}

// 4. History
$history = mysqli_query($conn, "
    SELECT m.machine_name as machine,
           dl.downtime_start,
           dl.downtime_end,
           TIMESTAMPDIFF(MINUTE, dl.downtime_start, dl.downtime_end) as duration
    FROM downtime_logs dl
    LEFT JOIN maintenance_logs ml ON ml.id = dl.maintenance_logs_id
    LEFT JOIN machine m ON m.id = ml.machine_id
    $whereHist
    ORDER BY dl.downtime_start DESC
    LIMIT 8
");
$histRows = [];
while ($r = mysqli_fetch_assoc($history)) $histRows[] = $r;

// Summary stats
$totalMachines   = count($dtRows);
$totalBreakdown  = array_sum(array_column($dtRows, 'breakdown'));
$totalDowntime   = array_sum(array_map(fn($r) => (int)($r['total_downtime'] ?? 0), $dtRows));
$avgAvailability = count($mttrRows) > 0 ? round(array_sum(array_column($mttrRows, 'availability')) / count($mttrRows), 1) : 0;

// JSON
$jDtMach  = json_encode(array_column($dtRows, 'machine'));
$jDtMin   = json_encode(array_map(fn($r) => (int)($r['total_downtime'] ?? 0), $dtRows));
$jDtBd    = json_encode(array_map(fn($r) => (int)$r['breakdown'], $dtRows));
$jT5Mach  = json_encode(array_column($topRows, 'machine'));
$jT5Tot   = json_encode(array_column($topRows, 'total'));
$jAvMach  = json_encode(array_column($mttrRows, 'machine'));
$jAvVal   = json_encode(array_column($mttrRows, 'availability'));
$jMttrVal = json_encode(array_column($mttrRows, 'mttr'));
$jMtbfVal = json_encode(array_column($mttrRows, 'mtbf'));
$jHiLabel = json_encode(array_map(fn($r) => ($r['machine'] ?? '-'), $histRows));
$jHiDur   = json_encode(array_column($histRows, 'duration'));
$jHiStart = json_encode(array_map(fn($r) => date('d/m H:i', strtotime($r['downtime_start'])), $histRows));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            background: #f0f2f5;
            color: #1a202c;
            font-family: -apple-system, 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            overflow: hidden;
            /* NO SCROLL */
        }

        /* ── Topbar ── */
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            height: 46px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .brand {
            font-size: 14px;
            font-weight: 800;
            color: #1a202c;
            letter-spacing: .01em;
        }

        .brand em {
            color: #3b82f6;
            font-style: normal;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .plant-sel {
            background: #f8fafc;
            border: 1px solid #cbd5e0;
            color: #1a202c;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            cursor: pointer;
            outline: none;
            font-weight: 600;
        }

        .plant-sel:focus {
            border-color: #3b82f6;
        }

        .btn-back {
            background: #f8fafc;
            border: 1px solid #cbd5e0;
            color: #4a5568;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: .15s;
        }

        .btn-back:hover {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        /* ── Main grid: fills remaining viewport ── */
        .main {
            height: calc(100vh - 46px);
            padding: 10px 14px;
            display: grid;
            grid-template-columns: 200px 1fr 1fr 1fr;
            grid-template-rows: 1fr 1fr 1fr;
            gap: 10px;
        }

        /* ── Cards ── */
        .card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            overflow: hidden;
        }

        .card-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        .ch {
            flex: 1;
            position: relative;
            min-height: 0;
            overflow: hidden;
        }

        /* ── KPI column (left) ── */
        .kpi-col {
            grid-column: 1;
            grid-row: 1 / 4;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .kpi-card {
            flex: 1;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
        }

        .kpi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .kpi-val {
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
            color: #1a202c;
        }

        .kpi-unit {
            font-size: 11px;
            font-weight: 600;
            color: #718096;
            margin-top: 2px;
        }

        .kpi-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #a0aec0;
        }

        /* ── Availability progress items ── */
        .av-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }

        .av-row {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            flex-shrink: 0;
        }

        .av-name {
            font-size: 10px;
            color: #2d3748;
            font-weight: 600;
            flex: 0 0 42%;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
        }

        .av-bg {
            flex: 1;
            height: 7px;
            background: #edf2f7;
            border-radius: 4px;
            overflow: hidden;
        }

        .av-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .6s ease;
        }

        .av-pct {
            font-size: 10px;
            font-weight: 800;
            width: 46px;
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ── History list items ── */
        .hist-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }

        .av-list::-webkit-scrollbar,
        .hist-list::-webkit-scrollbar {
            width: 6px;
        }

        .av-list::-webkit-scrollbar-thumb,
        .hist-list::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }

        .hist-item {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border-radius: 6px;
            padding: 6px 8px;
        }

        .hist-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .hist-mach {
            font-size: 11px;
            font-weight: 700;
            color: #2d3748;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hist-time {
            font-size: 10px;
            color: #718096;
            flex-shrink: 0;
        }

        .hist-dur {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 10px;
            flex-shrink: 0;
        }

        /* ── Top 5 donut + legend (nama process tampil penuh) ── */
        .donut-wrap {
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .donut-wrap .ch {
            flex: 0 0 46%;
            height: 100%;
        }

        .donut-legend {
            flex: 1;
            min-width: 0;
            max-height: 100%;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 7px;
            padding-right: 4px;
        }

        .dl-item {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            flex-shrink: 0;
        }

        .dl-sw {
            width: 10px;
            height: 10px;
            border-radius: 2px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .dl-name {
            font-size: 10px;
            font-weight: 600;
            color: #2d3748;
            line-height: 1.3;
            white-space: normal;
            word-break: break-word;
        }

        .dl-name small {
            display: block;
            font-size: 9px;
            font-weight: 600;
            color: #718096;
        }

        /* Tooltip donat: elemen HTML di luar card agar tidak terpotong */
        #chartTip {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity .1s;
            background: #1a202c;
            color: #e2e8f0;
            border: 1px solid #4a5568;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.35;
            max-width: 260px;
            white-space: normal;
        }

        #chartTip .ct-title {
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        #chartTip .ct-sw {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
            margin-right: 6px;
        }

        /* span helpers */
        .span2c {
            grid-column: span 2;
        }

        .span3c {
            grid-column: span 3;
        }
    </style>
</head>

<body>

    <!-- Topbar -->
    <div class="topbar">
        <div class="brand">Maintenance <em>Performance</em> Dashboard</div>
        <div class="topbar-right">
            <form method="get" style="margin:0; display:flex; gap:8px">
                <select name="month" class="plant-sel" onchange="this.form.submit()">
                    <option value="0" <?= $selectedMonth === 0 ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach ($monthNames as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $selectedMonth === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="plant-sel" onchange="this.form.submit()">
                    <option value="0" <?= $selectedYear === 0 ? 'selected' : '' ?>>Semua Tahun</option>
                    <?php foreach ($years as $yv): ?>
                        <option value="<?= $yv ?>" <?= $selectedYear === $yv ? 'selected' : '' ?>><?= $yv ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="plant" class="plant-sel" onchange="this.form.submit()">
                    <option value="all" <?= $selectedPlant == 'all' ? 'selected' : '' ?>>All Plants</option>
                    <?php foreach ($plants as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selectedPlant == $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="monitor.php" class="btn-back">← Back</a>
        </div>
    </div>

    <!-- Main dashboard grid -->
    <div class="main">

        <!-- ── KPI COLUMN (col 1, rows 1-3) ── -->
        <div class="kpi-col">

            <div class="kpi-card">
                <div>
                    <div class="kpi-icon" style="background:#ebf5ff">🏭</div>
                    <div class="kpi-val" style="color:#3b82f6"><?= $totalMachines ?></div>
                    <div class="kpi-unit">Total Process</div>
                </div>
                <div class="kpi-title">Terdaftar</div>
            </div>

            <div class="kpi-card">
                <div>
                    <div class="kpi-icon" style="background:#fff5f5">⚡</div>
                    <div class="kpi-val" style="color:#e53e3e"><?= $totalBreakdown ?></div>
                    <div class="kpi-unit">Total Breakdown</div>
                </div>
                <div class="kpi-title">Kejadian</div>
            </div>

            <div class="kpi-card">
                <div>
                    <div class="kpi-icon" style="background:#fffbeb">⏱</div>
                    <div class="kpi-val" style="color:#d97706; font-size:20px"><?= formatDuration($totalDowntime) ?></div>
                    <div class="kpi-unit">Total Downtime</div>
                </div>
                <div class="kpi-title">Akumulasi</div>
            </div>

            <div class="kpi-card">
                <div>
                    <div class="kpi-icon" style="background:#f0fff4">✅</div>
                    <div class="kpi-val" style="color:<?= $avgAvailability >= 98 ? '#38a169' : ($avgAvailability >= 95 ? '#d97706' : '#e53e3e') ?>"><?= $avgAvailability ?>%</div>
                    <div class="kpi-unit">Avg Availability</div>
                </div>
                <div class="kpi-title">Semua Process</div>
            </div>

        </div>

        <!-- ── ROW 1 ── -->

        <!-- Downtime bar (col 2, row 1) -->
        <div class="card">
            <div class="card-label">Total Downtime per Process (menit)</div>
            <div class="ch"><canvas id="cDtBar"></canvas></div>
        </div>

        <!-- Breakdown count bar (col 3, row 1) -->
        <div class="card">
            <div class="card-label">Jumlah Breakdown per Process</div>
            <div class="ch"><canvas id="cBdBar"></canvas></div>
        </div>

        <!-- Top 5 doughnut (col 4, row 1) -->
        <div class="card">
            <div class="card-label">Top 5 — Frekuensi Breakdown</div>
            <div class="donut-wrap">
                <div class="ch"><canvas id="cTop5"></canvas></div>
                <div class="donut-legend" id="t5Legend"></div>
            </div>
        </div>

        <!-- ── ROW 2 ── -->

        <!-- MTTR bar (col 2, row 2) -->
        <div class="card">
            <div class="card-label">MTTR — Mean Time to Repair (menit)</div>
            <div class="ch"><canvas id="cMttr"></canvas></div>
        </div>

        <!-- MTBF bar (col 3, row 2) -->
        <div class="card">
            <div class="card-label">MTBF — Mean Time Between Failures (jam)</div>
            <div class="ch"><canvas id="cMtbf"></canvas></div>
        </div>

        <!-- Availability progress list (col 4, row 2) -->
        <div class="card">
            <div class="card-label">Availability per Process (%)</div>
            <div class="av-list" id="avList"></div>
        </div>

        <!-- ── ROW 3 ── -->

        <!-- History bar chart (col 2-3, row 3) -->
        <div class="card span2c">
            <div class="card-label">Durasi Downtime Terbaru (menit)</div>
            <div class="ch"><canvas id="cHist"></canvas></div>
        </div>

        <!-- History list (col 4, row 3) -->
        <div class="card">
            <div class="card-label">Riwayat Downtime Terbaru</div>
            <div class="hist-list" id="histList"></div>
        </div>

    </div>

    <script>
        Chart.defaults.color = '#4a5568';
        Chart.defaults.borderColor = '#e2e8f0';
        Chart.defaults.font.family = "-apple-system,'Segoe UI',Arial,sans-serif";
        Chart.defaults.font.size = 10;

        const dtMach = <?= $jDtMach ?>;
        const dtMin = <?= $jDtMin ?>;
        const dtBd = <?= $jDtBd ?>;
        const t5Mach = <?= $jT5Mach ?>;
        const t5Tot = <?= $jT5Tot ?>;
        const avMach = <?= $jAvMach ?>;
        const avVal = <?= $jAvVal ?>;
        const mttrVal = <?= $jMttrVal ?>;
        const mtbfVal = <?= $jMtbfVal ?>;
        const hiLabel = <?= $jHiLabel ?>;
        const hiDur = <?= $jHiDur ?>;
        const hiStart = <?= $jHiStart ?>;

        const pie5 = ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6'];
        const avColors = avVal.map(v => v >= 98 ? '#38a169' : v >= 95 ? '#d97706' : '#e53e3e');
        const hiColors = hiDur.map(v => v > 30 ? '#ef4444' : v > 15 ? '#f59e0b' : '#10b981');

        const tt = (extra = {}) => ({
            backgroundColor: '#1a202c',
            titleColor: '#ffffff',
            bodyColor: '#e2e8f0',
            borderColor: '#4a5568',
            borderWidth: 1,
            padding: 8,
            ...extra
        });

        // Shared horizontal bar opts
        function hbarOpts(unit, color) {
            return {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...tt(),
                        callbacks: {
                            label: c => ` ${c.raw} ${unit}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f0f2f5'
                        },
                        ticks: {
                            color: '#718096'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#2d3748',
                            font: {
                                size: 10,
                                weight: '600'
                            }
                        }
                    }
                }
            };
        }

        // 1. Downtime bar
        new Chart('cDtBar', {
            type: 'bar',
            data: {
                labels: dtMach,
                datasets: [{
                    data: dtMin,
                    backgroundColor: dtMin.map((v, i) => `rgba(59,130,246,${Math.max(0.4, 1 - i*0.05)})`),
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: hbarOpts('menit', '#3b82f6')
        });

        // 2. Breakdown count
        new Chart('cBdBar', {
            type: 'bar',
            data: {
                labels: dtMach,
                datasets: [{
                    data: dtBd,
                    backgroundColor: dtBd.map((v, i) => `rgba(239,68,68,${Math.max(0.4, 1 - i*0.05)})`),
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: hbarOpts('x breakdown', '#ef4444')
        });

        // Plugin: persentase horizontal (tegak) di tengah tiap potongan cincin donat
        const donutPct = {
            id: 'donutPct',
            afterDatasetsDraw(chart) {
                const {
                    ctx
                } = chart;
                const data = chart.data.datasets[0].data.map(Number);
                const total = data.reduce((a, b) => a + b, 0);
                if (!total) return;
                chart.getDatasetMeta(0).data.forEach((arc, i) => {
                    if (!data[i]) return;
                    const pos = arc.getCenterPoint();
                    const arcLen = (arc.endAngle - arc.startAngle) * (arc.innerRadius + arc.outerRadius) / 2;
                    if (arcLen < 14) return; // potongan terlalu sempit, lihat tooltip
                    const txt = Math.round(data[i] / total * 100) + '%';
                    const thick = arc.outerRadius - arc.innerRadius;
                    const font = sz => `700 ${sz}px -apple-system,"Segoe UI",Arial,sans-serif`;
                    ctx.save();
                    // ukuran font menyesuaikan ketebalan cincin agar tulisan selalu di dalam grafik
                    let size = 11;
                    ctx.font = font(size);
                    const w = ctx.measureText(txt).width;
                    if (w > thick * 0.8) size = Math.floor(size * thick * 0.8 / w);
                    if (size < 7) {
                        ctx.restore();
                        return;
                    }
                    ctx.font = font(size);
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.lineJoin = 'round';
                    ctx.lineWidth = 2.5;
                    ctx.strokeStyle = 'rgba(0,0,0,.35)';
                    ctx.fillStyle = '#ffffff';
                    ctx.strokeText(txt, pos.x, pos.y);
                    ctx.fillText(txt, pos.x, pos.y);
                    ctx.restore();
                });
            }
        };

        // Tooltip donat (HTML, menempel di body supaya tidak terpotong card/canvas)
        function donutTooltip(context) {
            const {
                chart,
                tooltip
            } = context;
            let el = document.getElementById('chartTip');
            if (!el) {
                el = document.createElement('div');
                el.id = 'chartTip';
                document.body.appendChild(el);
            }
            if (tooltip.opacity === 0 || !tooltip.dataPoints || !tooltip.dataPoints.length) {
                el.style.opacity = 0;
                return;
            }
            const dp = tooltip.dataPoints[0];
            const tot = dp.dataset.data.reduce((a, b) => a + Number(b), 0);
            const pct = tot ? (dp.raw / tot * 100).toFixed(1) : 0;
            el.innerHTML = `<div class="ct-title">${dp.label}</div>
                <div><span class="ct-sw" style="background:${pie5[dp.dataIndex]}"></span>${dp.raw}x breakdown (${pct}%)</div>`;
            const rect = chart.canvas.getBoundingClientRect();
            const x = rect.left + tooltip.caretX;
            const y = rect.top + tooltip.caretY;
            const w = el.offsetWidth,
                h = el.offsetHeight;
            let left = x + 14;
            if (left + w > window.innerWidth - 8) left = x - w - 14;
            left = Math.max(8, left);
            let top = y - h / 2;
            top = Math.max(8, Math.min(top, window.innerHeight - h - 8));
            el.style.left = left + 'px';
            el.style.top = top + 'px';
            el.style.opacity = 1;
        }

        // 3. Top 5 doughnut
        new Chart('cTop5', {
            type: 'doughnut',
            plugins: [donutPct],
            data: {
                labels: t5Mach,
                datasets: [{
                    data: t5Tot,
                    backgroundColor: pie5,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '40%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false,
                        external: donutTooltip
                    }
                }
            }
        });

        // Legend HTML Top 5 (nama process penuh, wrap, scrollable bila panjang)
        const t5Legend = document.getElementById('t5Legend');
        const t5Sum = t5Tot.reduce((a, b) => a + Number(b), 0);
        t5Mach.forEach((m, i) => {
            const pct = t5Sum ? (t5Tot[i] / t5Sum * 100).toFixed(1) : 0;
            t5Legend.innerHTML += `
    <div class="dl-item">
      <div class="dl-sw" style="background:${pie5[i]}"></div>
      <div class="dl-name">${m}<small>${t5Tot[i]}x · ${pct}%</small></div>
    </div>`;
        });

        // 4. MTTR
        new Chart('cMttr', {
            type: 'bar',
            data: {
                labels: avMach,
                datasets: [{
                    data: mttrVal,
                    backgroundColor: 'rgba(245,158,11,.75)',
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: hbarOpts('menit', '#f59e0b')
        });

        // 5. MTBF
        new Chart('cMtbf', {
            type: 'bar',
            data: {
                labels: avMach,
                datasets: [{
                    data: mtbfVal,
                    backgroundColor: 'rgba(139,92,246,.75)',
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: hbarOpts('jam', '#8b5cf6')
        });

        // 6. Availability progress list (HTML)
        const avList = document.getElementById('avList');
        avMach.forEach((m, i) => {
            const v = avVal[i];
            const col = v >= 98 ? '#38a169' : v >= 95 ? '#d97706' : '#e53e3e';
            avList.innerHTML += `
    <div class="av-row">
      <div class="av-name" title="${m}">${m}</div>
      <div class="av-bg"><div class="av-fill" style="width:${Math.max(0,v-80)/20*100}%;background:${col}"></div></div>
      <div class="av-pct" style="color:${col}">${v}%</div>
    </div>`;
        });

        // 7. History bar
        new Chart('cHist', {
            type: 'bar',
            data: {
                labels: hiLabel,
                datasets: [{
                    data: hiDur,
                    backgroundColor: hiColors,
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...tt(),
                        callbacks: {
                            label: c => ` ${c.raw} menit`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#2d3748',
                            font: {
                                size: 10,
                                weight: '600'
                            },
                            maxRotation: 25
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f5'
                        },
                        ticks: {
                            color: '#718096'
                        }
                    }
                }
            }
        });

        // 8. History list (HTML)
        const histList = document.getElementById('histList');
        hiLabel.forEach((m, i) => {
            const dur = hiDur[i];
            const col = dur > 30 ? '#e53e3e' : dur > 15 ? '#d97706' : '#38a169';
            const bg = dur > 30 ? '#fff5f5' : dur > 15 ? '#fffbeb' : '#f0fff4';
            histList.innerHTML += `
    <div class="hist-item">
      <div class="hist-dot" style="background:${col}"></div>
      <div class="hist-mach">${m}</div>
      <div class="hist-time">${hiStart[i]}</div>
      <div class="hist-dur" style="background:${bg};color:${col}">${dur}m</div>
    </div>`;
        });
    </script>
</body>

</html>