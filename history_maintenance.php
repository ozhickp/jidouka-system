<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>History Maintenance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-size: 14px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* TABLE */

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
            background: #198754;
            color: white;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background: #eef8f1;
        }

        .table-responsive {
            max-height: 650px;
            overflow: auto;
        }

        /* sticky header */

        .table-responsive thead th {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        /* sticky machine column */

        .table th:nth-child(2),
        .table td:nth-child(2) {
            position: sticky;
            left: 0;
            min-width: 160px;
            background: #fff;
            z-index: 15;
        }

        .table thead th:nth-child(2) {
            background: #198754;
            z-index: 25;
        }

        .machine-col {
            font-weight: 600;
            color: #0d6efd;
        }

        .text-limit {
            max-width: 220px;
            white-space: normal;
            overflow: hidden;
        }

        .duration-badge {
            background: #e8f5e9;
            color: #198754;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        .doc-btn {
            padding: 2px 6px;
            font-size: 11px;
        }

        .table th:nth-child(8),
        .table td:nth-child(8),
        .table th:nth-child(9),
        .table td:nth-child(9) {
            width: 90px;
        }

        tfoot td {
            background: #e8f5e9;
            font-size: 14px;
            font-weight: 600;
        }
    </style>

</head>

<body>

    <div class="container mt-5">

        <!-- HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <a href="monitor.php" class="btn btn-outline-secondary">
                ← Back
            </a>

            <h3 class="m-0 text-center flex-grow-1">
                History Maintenance
            </h3>

            <div style="width:120px;"></div>

        </div>


        <!-- EXPORT -->

        <div class="card shadow-sm p-4 mb-4">

            <div class="row">

                <div class="col-md-6">

                    <button class="btn btn-success w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#exportDailyModal">

                        Export Daily Report

                    </button>

                </div>

                <div class="col-md-6">

                    <button class="btn btn-success w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#exportMonthlyModal">

                        Export Monthly Report

                    </button>

                </div>

            </div>

        </div>


        <!-- FILTER -->

        <div class="card shadow-sm p-4 mb-4">

            <h6 class="mb-3 text-success fw-semibold">
                Pilih data yang ingin ditampilkan
            </h6>

            <div class="row align-items-end">

                <div class="col-md-4">

                    <label class="form-label small">Per Hari</label>

                    <input type="date"
                        id="filterDate"
                        class="form-control form-control-sm"
                        value="<?= date('Y-m-d') ?>">

                </div>

                <div class="col-md-4">

                    <label class="form-label small">Per Bulan</label>

                    <input type="month"
                        id="filterMonth"
                        class="form-control form-control-sm">

                </div>

                <div class="col-md-4">

                    <label class="form-label small">Per Tahun</label>

                    <input type="number"
                        id="filterYear"
                        class="form-control form-control-sm"
                        placeholder="2025"
                        min="2000"
                        max="2100">

                </div>

            </div>

        </div>


        <!-- TABLE -->

        <div class="card shadow-sm p-4">

            <h5 class="mb-3">Data History Maintenance</h5>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle text-center">

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

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Documentation Preview
                    </h5>

                    <button class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body text-center">

                    <img id="previewImage" src="" class="img-fluid">

                </div>

            </div>

        </div>

    </div>


    <!-- MODAL EXPORT DAILY -->

    <div class="modal fade" id="exportDailyModal">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Export Daily Report
                    </h5>

                    <button class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <form method="GET" action="export_daily.php">

                    <div class="modal-body">

                        <label class="form-label">
                            Pilih Tanggal
                        </label>

                        <input type="date"
                            name="tanggal"
                            class="form-control"
                            required>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit"
                            class="btn btn-success">

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

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Export Monthly Report
                    </h5>

                    <button class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <form method="GET" action="export_monthly.php">

                    <div class="modal-body">

                        <label class="form-label">
                            Pilih Bulan
                        </label>

                        <input type="month"
                            name="bulan"
                            class="form-control"
                            required>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit"
                            class="btn btn-success">

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

            setInterval(refreshData, 2000);

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