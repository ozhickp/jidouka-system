<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin - History Maintenance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #f1f5f9;
        }

        .card-custom {
            background: #1e293b;
            border: none;
            border-radius: 15px;
        }

        .table thead {
            background: #334155;
            color: white;
        }

        .btn-admin {
            background: #0ea5e9;
            color: white;
        }

        .btn-admin:hover {
            background: #0284c7;
            color: white;
        }

        .page-title {
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* TABLE UPDATE */

        .table {
            font-size: 13px;
            white-space: nowrap;
            text-align: center;
        }

        .table-responsive {
            max-height: 650px;
            overflow: auto;
        }

        .table-responsive thead th {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            position: sticky;
            left: 0;
            min-width: 160px;
            background: #1e293b;
            z-index: 15;
        }

        .table thead th:nth-child(2) {
            background: #334155;
            z-index: 25;
        }

        .text-limit {
            max-width: 220px;
            white-space: normal;
        }

        .duration-badge {
            background: #0ea5e9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        tfoot td {
            background: #1e293b;
            font-weight: 600;
        }
    </style>

</head>

<body>

    <div class="container mt-4">

        <!-- HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <a href="dashboard_admin.php" class="btn btn-outline-light">
                ← Dashboard
            </a>

            <h3 class="page-title text-center flex-grow-1">
                ADMIN - HISTORY MAINTENANCE
            </h3>

            <div style="width:120px;"></div>

        </div>

        <!-- EXPORT BUTTON -->

        <div class="card card-custom shadow p-4 mb-4">

            <div class="row">

                <div class="col-md-6">
                    <button class="btn btn-admin w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#exportDailyModal">
                        Export Daily Report
                    </button>
                </div>

                <div class="col-md-6">
                    <button class="btn btn-admin w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#exportMonthlyModal">
                        Export Monthly Report
                    </button>
                </div>

            </div>

        </div>

        <!-- FILTER -->

        <div class="card card-custom shadow p-4 mb-4">

            <h6 class="mb-3 text-info fw-semibold">
                Filter Data Maintenance
            </h6>

            <div class="row align-items-end">

                <div class="col-md-4">
                    <label class="form-label small text-light">Per Hari</label>
                    <input type="date" id="filterDate"
                        class="form-control"
                        value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label small text-light">Per Bulan</label>
                    <input type="month" id="filterMonth"
                        class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label small text-light">Per Tahun</label>
                    <input type="number" id="filterYear"
                        class="form-control"
                        placeholder="2025"
                        min="2000" max="2100">
                </div>

            </div>

        </div>

        <!-- TABLE -->

        <div class="card card-custom shadow p-4">

            <h5 class="mb-3 text-light">Data History Maintenance</h5>

            <div class="table-responsive">

                <table class="table table-dark table-hover table-bordered align-middle text-center">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Machine</th>
                            <th>Plant</th>
                            <th>Machine Problem</th>
                            <th>Machine Action</th>
                            <th>Engine Problem</th>
                            <th>Engine Action</th>
                            <th>Documentation Machine</th>
                            <th>Documentation Engine</th>
                            <th>Handled By</th>
                            <th>Downtime Start</th>
                            <th>Downtime End</th>
                            <th>Maintenance Start</th>
                            <th>Maintenance End</th>
                            <th>Downtime Duration</th>
                            <th>Maintenance Duration</th>
                        </tr>

                    </thead>

                    <tbody id="maintenanceBody"></tbody>

                    <tfoot>

                        <tr>

                            <td colspan="14">
                                TOTAL
                            </td>

                            <td id="totalDowntime">
                                00:00:00
                            </td>

                            <td id="totalMaintenance">
                                00:00:00
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>


    <!-- MODAL IMAGE -->

    <div class="modal fade" id="imageModal">

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content bg-dark text-light">

                <div class="modal-header border-0">
                    <h5 class="modal-title">Dokumentasi Maintenance</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img id="previewImage" src="" class="img-fluid rounded">
                </div>

            </div>

        </div>

    </div>


    <!-- MODAL EXPORT DAILY -->

    <div class="modal fade" id="exportDailyModal">

        <div class="modal-dialog">

            <div class="modal-content bg-dark text-light">

                <div class="modal-header border-0">
                    <h5 class="modal-title">Export Daily Report</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form method="GET" action="export_daily.php">

                    <div class="modal-body">
                        <label class="form-label">Pilih Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>

                    <div class="modal-footer border-0">

                        <button class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button class="btn btn-admin">
                            Export
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- MODAL EXPORT MONTHLY -->

    <div class="modal fade" id="exportMonthlyModal">

        <div class="modal-dialog">

            <div class="modal-content bg-dark text-light">

                <div class="modal-header border-0">
                    <h5 class="modal-title">Export Monthly Report</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form method="GET" action="export_monthly.php">

                    <div class="modal-body">
                        <label class="form-label">Pilih Bulan</label>
                        <input type="month" name="bulan" class="form-control" required>
                    </div>

                    <div class="modal-footer border-0">

                        <button class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button class="btn btn-admin">
                            Export
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentFilterType = "daily";
        let currentFilterValue = $("#filterDate").val();

        function loadMaintenance(type, value) {

            $.ajax({

                url: "get_maintenance_data.php",
                type: "GET",

                data: {
                    filterType: type,
                    filterValue: value
                },

                success: function(data) {

                    $("#maintenanceBody").html(data);

                }

            });

        }

        function refreshData() {

            if (currentFilterValue !== "")
                loadMaintenance(currentFilterType, currentFilterValue);

        }

        $(document).ready(function() {

            refreshData();

            setInterval(function() {
                refreshData();
            }, 3000);

            $("#filterDate").on("change", function() {

                $("#filterMonth").val("");
                $("#filterYear").val("");

                currentFilterType = "daily";
                currentFilterValue = $(this).val();

                refreshData();

            });

            $("#filterMonth").on("change", function() {

                $("#filterDate").val("");
                $("#filterYear").val("");

                currentFilterType = "monthly";
                currentFilterValue = $(this).val();

                refreshData();

            });

            $("#filterYear").on("keyup change", function() {

                $("#filterDate").val("");
                $("#filterMonth").val("");

                if ($(this).val() !== "") {

                    currentFilterType = "yearly";
                    currentFilterValue = $(this).val();

                    refreshData();

                }

            });

            $(document).on("click", ".view-image", function() {

                let imagePath = $(this).data("image");

                $("#previewImage").attr("src", imagePath);

                new bootstrap.Modal(document.getElementById('imageModal')).show();

            });

        });
    </script>

</body>

</html>