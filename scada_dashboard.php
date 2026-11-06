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
    <title>SCADA Realtime Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        body {
            background: #0f172a;
            color: #ffffff;
            font-family: Arial;
        }

        h2,
        h5 {
            color: #ffffff;
        }

        .card {
            background: #1e293b;
            border: none;
            color: #ffffff;
        }

        .machine-box {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            color: #ffffff;
        }

        .run {
            background: #16a34a;
        }

        .down {
            background: #dc2626;
        }

        .maintenance {
            background: #f59e0b;
        }

        .metric {
            font-size: 26px;
            font-weight: bold;
            color: #ffffff;
        }

        table,
        table th,
        table td {
            color: #ffffff;
        }

        .red {
            color: #ff5555;
            font-weight: bold;
        }

        a.btn-secondary {
            color: #ffffff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-4">
        <h2 class="text-center mb-4">Industrial Maintenance SCADA Realtime Dashboard</h2>

        <!-- MACHINE STATUS -->
        <div id="machineStatus" class="row mb-4"></div>

        <!-- KPI -->
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card p-3">Total Downtime<div class="metric" id="totalDowntime">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">Total Breakdown<div class="metric" id="totalBreakdown">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">MTTR<div class="metric" id="mttr">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">Availability<div class="metric" id="availability">0</div>
                </div>
            </div>
        </div>

        <!-- Downtime Chart -->
        <div class="card p-3 mb-4">
            <h5>Downtime per Machine</h5>
            <canvas id="downtimeChart"></canvas>
        </div>

        <!-- Top Breakdown -->
        <div class="card p-3 mb-4">
            <h5>Top Machine Breakdown</h5>
            <table class="table table-bordered" id="topBreakdownTable"></table>
        </div>

        <!-- Latest Downtime -->
        <div class="card p-3 mb-4">
            <h5>Latest Downtime</h5>
            <table class="table table-bordered" id="latestDowntimeTable"></table>
        </div>

        <div class="mt-3 mb-5">
            <a href="monitor.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <script>
        var ctx = document.getElementById('downtimeChart');
        var downtimeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Downtime (minutes)',
                    data: [],
                    backgroundColor: 'red'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: "#ffffff"
                        }
                    },
                    y: {
                        ticks: {
                            color: "#ffffff"
                        }
                    }
                }
            }
        });

        // Fungsi natural sort untuk nama mesin
        function naturalSort(a, b) {
            return a.localeCompare(b, undefined, {
                numeric: true,
                sensitivity: 'base'
            });
        }

        function loadDashboard() {
            $.getJSON('scada_data.php', function(data) {
                // Urutkan machine berdasarkan plant dan nama machine secara natural
                data.machines.sort((a, b) => {
                    if (a.plant === b.plant) {
                        return naturalSort(a.machine, b.machine);
                    } else {
                        return a.plant.localeCompare(b.plant);
                    }
                });

                // Render machine status
                let htmlStatus = '';
                data.machines.forEach(m => {
                    let cls = m.status === 'RUN' ? 'run' : 'down';
                    htmlStatus += `<div class="col-md-2 mb-3">
                        <div class="machine-box ${cls}">${m.plant} - ${m.machine}<br>${m.status}</div>
                    </div>`;
                });
                $('#machineStatus').html(htmlStatus);

                // KPI
                $('#totalDowntime').text(data.kpi.totalDowntime);
                $('#totalBreakdown').text(data.kpi.totalBreakdown);
                $('#mttr').text(data.kpi.mttr);
                $('#availability').text(data.kpi.availability);

                // Downtime Chart
                downtimeChart.data.labels = data.chart.labels;
                downtimeChart.data.datasets[0].data = data.chart.data;
                downtimeChart.update();

                // Top Breakdown Table
                let topHtml = '<tr><th>Rank</th><th>Machine</th><th>Breakdown</th></tr>';
                data.top.forEach((t, i) => topHtml += `<tr><td>${i+1}</td><td>${t.machine}</td><td>${t.total}</td></tr>`);
                $('#topBreakdownTable').html(topHtml);

                // Latest Downtime Table
                let latestHtml = '<tr><th>Machine</th><th>Start</th><th>End</th><th>Duration</th></tr>';
                data.latest.forEach(l => {
                    let dur = l.duration > 30 ? `<span class='red'>${l.duration} min</span>` : `${l.duration} min`;
                    latestHtml += `<tr><td>${l.machine}</td><td>${l.start}</td><td>${l.end}</td><td>${dur}</td></tr>`;
                });
                $('#latestDowntimeTable').html(latestHtml);
            });
        }

        // Reload setiap 5 detik
        setInterval(loadDashboard, 5000);
        loadDashboard();
    </script>
</body>

</html>