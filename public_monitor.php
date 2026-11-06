<?php
include 'config.php';

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';
$tv_monitor = true; // Force TV/public read-only mode
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Public Machine Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; }
        .plant-selector { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-machine { border-radius: 12px; transition: transform 0.3s; }
        .card-machine:hover { transform: translateY(-5px); }
        .status-running { border-left: 5px solid #28a745 !important; }
        .status-abnormal { border-left: 5px solid #dc3545 !important; }
        .status-maintenance { border-left: 5px solid #ffc107 !important; }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <!-- Plant Selector -->
        <div class="plant-selector mx-auto text-center" style="max-width: 600px;">
            <h2><i class="fas fa-industry me-3"></i>Public Machine Monitoring</h2>
            <div class="btn-group w-100" role="group">
                <a href="?plant=assembly" class="btn btn-outline-primary <?= $plant=='assembly' ? 'active bg-primary text-white' : '' ?>">Plant Assembly</a>
                <a href="?plant=test%20run" class="btn btn-outline-primary <?= $plant=='test run' ? 'active bg-primary text-white' : '' ?>">Test Run</a>
                <a href="?plant=packing" class="btn btn-outline-primary <?= $plant=='packing' ? 'active bg-primary text-white' : '' ?>">Packing</a>
            </div>
        </div>

        <!-- Machine Cards -->
        <h3 class="text-center mb-4">Status Mesin - <?= ucfirst($plant) ?></h3>
        <div class="row" id="machineContainer">
            <?php ob_start(); ?>
            <?php include 'get_machine_data.php?plant=' . urlencode($plant) . '&tv_monitor=1'; ?>
            <?php $machine_html = ob_get_clean(); ?>
            <?php echo $machine_html; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5s for public view
        setInterval(() => {
            location.reload();
        }, 5000);
    </script>
</body>
</html>
?>

