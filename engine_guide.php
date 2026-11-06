<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user'])) die("Silahkan login terlebih dahulu.");

$machine_id = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;

$stmt = $conn->prepare("SELECT engine_name FROM engine ORDER BY engine_name ASC");
$stmt->execute();
$engines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Engine List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #engine_table tbody tr {
            transition: all 0.2s;
            cursor: pointer;
        }

        #engine_table tbody tr:hover {
            background-color: #f0f8ff;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Daftar Tipe Engine</h4>
            </div>
            <div class="card-body">
                <p>Klik salah satu tipe engine untuk otomatis mengisi field engine di form maintenance.</p>

                <div class="mb-3">
                    <input type="text" id="engine_search" class="form-control" placeholder="Cari tipe engine...">
                </div>

                <table class="table table-bordered table-striped" id="engine_table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Engine Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($engines as $idx => $engine): ?>
                            <tr data-engine="<?= htmlspecialchars($engine['engine_name']); ?>">
                                <td><?= $idx + 1 ?></td>
                                <td><?= htmlspecialchars($engine['engine_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('engine_search');
        const tableRows = document.querySelectorAll('#engine_table tbody tr');
        const machineId = <?= $machine_id ?>;

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            let visibleCount = 0;
            tableRows.forEach(row => {
                const engineName = row.dataset.engine.toLowerCase();
                if (engineName.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else row.style.display = 'none';
            });
            if (visibleCount === 0) {
                if (!document.getElementById('no_result')) {
                    const tbody = document.querySelector('#engine_table tbody');
                    const tr = document.createElement('tr');
                    tr.id = 'no_result';
                    const td = document.createElement('td');
                    td.colSpan = 2;
                    td.classList.add('text-center', 'text-muted');
                    td.textContent = 'Tidak ada tipe yang sesuai';
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                }
            } else {
                const noResultRow = document.getElementById('no_result');
                if (noResultRow) noResultRow.remove();
            }
        });

        // Klik untuk pilih engine dan kembali ke form_maintenance
        tableRows.forEach(row => {
            row.addEventListener('click', () => {
                const engineName = encodeURIComponent(row.dataset.engine);
                window.location.href = `form_maintenance.php?machine_id=${machineId}&engine_name=${engineName}`;
            });
        });
    </script>
</body>

</html>