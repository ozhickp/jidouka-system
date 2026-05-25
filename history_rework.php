<?php
session_start();
include 'config.php';

// Izinkan akses untuk user ATAU admin
$is_user  = isset($_SESSION['user']);
$is_admin = isset($_SESSION['admin_id']);

if (!$is_user && !$is_admin) {
    header("Location: index.php");
    exit;
}

// Tentukan halaman kembali sesuai role
$back_url = $is_admin ? "dashboard_admin.php" : "monitor.php";

// Filter
$plant_filter = isset($_GET['plant']) ? $_GET['plant'] : '';
$date_from    = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to      = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where  = ["lml.mode = 'rework' OR (lml.mode = 'production' AND lml.form_filled = 1)"];
$params = [];
$types  = '';

if ($plant_filter !== '') {
    $where[]  = "lml.plant = ?";
    $params[] = $plant_filter;
    $types   .= 's';
}
if ($date_from !== '') {
    $where[]  = "DATE(lml.start_time) >= ?";
    $params[] = $date_from;
    $types   .= 's';
}
if ($date_to !== '') {
    $where[]  = "DATE(lml.start_time) <= ?";
    $params[] = $date_to;
    $types   .= 's';
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        lml.id,
        lml.plant,
        lml.start_time,
        lml.end_time,
        lml.form_filled,
        lml.engine_serial,
        lml.corrective_action,
        e.engine_name,
        u.name AS operator_name,
        TIMESTAMPDIFF(SECOND, lml.start_time, lml.end_time) AS duration_sec
    FROM line_mode_logs lml
    LEFT JOIN engine e ON e.id = lml.engine_id
    LEFT JOIN user u   ON u.id = lml.created_by
    $where_sql
    ORDER BY lml.id DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);

// Helper: format duration
function formatDuration($sec)
{
    if ($sec === null || $sec < 0) return '-';
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    $s = $sec % 60;
    if ($h > 0) return "{$h}j {$m}m {$s}d";
    if ($m > 0) return "{$m}m {$s}d";
    return "{$s}d";
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>History Rework</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
            font-size: 14px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
        }

        .table {
            font-size: 13px;
            white-space: nowrap;
            text-align: center;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table thead th {
            background: #e67e22;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .table tbody tr:hover {
            background: #fff8f0;
        }

        .table-responsive {
            max-height: 620px;
            overflow: auto;
        }

        /* sticky no column */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            position: sticky;
            left: 0;
            min-width: 50px;
            background: #fff;
            z-index: 15;
        }

        .table thead th:nth-child(1) {
            background: #e67e22;
            z-index: 25;
        }

        .duration-badge {
            background: #fef3e2;
            color: #e67e22;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        .badge-filled {
            background: #198754;
            color: #fff;
        }

        .badge-unfilled {
            background: #dc3545;
            color: #fff;
        }

        tfoot td {
            background: #fef3e2;
            font-size: 14px;
            font-weight: 600;
        }

        .text-limit {
            max-width: 200px;
            white-space: normal;
        }

        .page-header {
            background: #e67e22;
            color: white;
            border-radius: 12px;
            padding: 16px 24px;
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4 mb-5 px-4">

        <!-- HEADER -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <a href="<?= $back_url ?>" class="btn btn-light btn-sm">← Back</a>
            <h4 class="m-0"><i class="fas fa-history me-2"></i>History Rework</h4>
            <div style="width:100px;"></div>
        </div>

        <!-- FILTER -->
        <div class="card p-4 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Plant</label>
                    <select name="plant" class="form-select">
                        <option value="">Semua Plant</option>
                        <option value="assembly" <?= $plant_filter == 'assembly'  ? 'selected' : '' ?>>Assembly</option>
                        <option value="test run" <?= $plant_filter == 'test run'  ? 'selected' : '' ?>>Test Run</option>
                        <option value="packing" <?= $plant_filter == 'packing'   ? 'selected' : '' ?>>Packing</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="history_rework.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>

        <!-- SUMMARY CARDS -->
        <?php
        $total       = count($rows);
        $filled      = count(array_filter($rows, fn($r) => $r['form_filled'] == 1));
        $not_filled  = $total - $filled;

        $total_sec   = array_sum(array_column($rows, 'duration_sec'));
        $avg_sec     = $total > 0 ? intdiv($total_sec, $total) : 0;
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <div class="text-muted small">Total Rework</div>
                    <div class="fs-3 fw-bold text-warning"><?= $total ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <div class="text-muted small">Form Terisi</div>
                    <div class="fs-3 fw-bold text-success"><?= $filled ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <div class="text-muted small">Form Belum Terisi</div>
                    <div class="fs-3 fw-bold text-danger"><?= $not_filled ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3">
                    <div class="text-muted small">Rata-rata Durasi</div>
                    <div class="fs-3 fw-bold text-warning"><?= formatDuration($avg_sec) ?></div>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Plant</th>
                            <th>Operator</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Durasi</th>
                            <th>Engine</th>
                            <th>Serial Engine</th>
                            <th>Corrective Action</th>
                            <th>Status Form</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="10" class="text-muted py-4">Tidak ada data rework ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <span class="badge bg-secondary text-capitalize">
                                            <?= htmlspecialchars($row['plant']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['operator_name'] ?? '-') ?></td>
                                    <td><?= $row['start_time'] ?? '-' ?></td>
                                    <td><?= $row['end_time']   ?? '<span class="text-danger">Belum selesai</span>' ?></td>
                                    <td>
                                        <span class="duration-badge">
                                            <?= formatDuration($row['duration_sec']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['engine_name']   ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['engine_serial']  ?? '-') ?></td>
                                    <td class="text-limit text-start">
                                        <?= htmlspecialchars($row['corrective_action'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php if ($row['form_filled'] == 1): ?>
                                            <span class="badge badge-filled">Terisi</span>
                                        <?php else: ?>
                                            <span class="badge badge-unfilled">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($rows)): ?>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end">Total Durasi Rework :</td>
                                <td colspan="5" class="text-start ps-3">
                                    <?= formatDuration($total_sec) ?>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>