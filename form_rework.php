<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$plant = $_GET['plant'] ?? 'assembly';
?>

<!DOCTYPE html>
<html>

<head>
    <title>Form Rework - Plant <?= htmlspecialchars($plant) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #engine_suggestions {
            position: absolute;
            z-index: 1000;
            width: 100%;
        }
    </style>
</head>

<body class="p-4">

    <div class="container">
        <h3>Form Rework - Plant <?= htmlspecialchars($plant) ?></h3>

        <form id="reworkForm">

            <input type="hidden" name="plant" value="<?= htmlspecialchars($plant) ?>">
            <input type="hidden" name="engine_id" id="engine_id">

            <div class="mb-3 position-relative">
                <label>Engine</label>
                <input type="text" id="engine_input" class="form-control" placeholder="Ketik nama engine..." autocomplete="off" required>
                <div id="engine_suggestions" class="list-group"></div>
            </div>

            <div class="mb-3">
                <label>Serial Number</label>
                <input type="text" name="engine_serial" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Corrective Action</label>
                <textarea name="corrective_action" class="form-control" required></textarea>
            </div>

            <button type="button" id="submitBtn" class="btn btn-primary w-100">Submit Rework</button>
        </form>
    </div>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Submit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menyimpan form rework ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirmSubmit">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Success -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3">
                <h5 class="text-success">Rework berhasil disimpan!</h5>
                <button type="button" class="btn btn-success mt-2" id="backMonitor">Kembali ke Monitor</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const engineInput = document.getElementById('engine_input');
        const engineIdInput = document.getElementById('engine_id');
        const suggestionBox = document.getElementById('engine_suggestions');
        const submitBtn = document.getElementById('submitBtn');

        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));

        engineInput.addEventListener('input', () => {
            const query = engineInput.value.trim();
            engineIdInput.value = '';
            if (query.length < 1) {
                suggestionBox.innerHTML = '';
                return;
            }
            fetch('search_engine.php?q=' + encodeURIComponent(query) + '&plant=<?= $plant ?>')
                .then(res => res.json())
                .then(data => {
                    suggestionBox.innerHTML = '';
                    if (data.length === 0) {
                        const div = document.createElement('div');
                        div.classList.add('list-group-item', 'text-muted');
                        div.textContent = 'Tidak ada tipe';
                        suggestionBox.appendChild(div);
                    } else {
                        data.forEach(item => {
                            const div = document.createElement('a');
                            div.href = '#';
                            div.classList.add('list-group-item', 'list-group-item-action');
                            div.textContent = item.engine_name;
                            div.addEventListener('click', e => {
                                e.preventDefault();
                                engineInput.value = item.engine_name;
                                engineIdInput.value = item.id;
                                suggestionBox.innerHTML = '';
                            });
                            suggestionBox.appendChild(div);
                        });
                    }
                });
        });

        document.addEventListener('click', e => {
            if (!engineInput.contains(e.target)) suggestionBox.innerHTML = '';
        });

        // Tampilkan modal konfirmasi sebelum submit
        submitBtn.addEventListener('click', () => {
            if (!engineIdInput.value || engineIdInput.value == "0") {
                alert("WAJIB pilih engine dari daftar, jangan ketik manual!");
                return;
            }
            confirmModal.show();
        });

        // Submit ketika konfirmasi
        document.getElementById('confirmSubmit').addEventListener('click', () => {
            let formData = new FormData(document.getElementById('reworkForm'));
            fetch('save_rework.php', {
                    method: 'POST',
                    body: formData
                }).then(res => res.json())
                .then(data => {
                    confirmModal.hide();
                    if (data.status === 'success') {
                        successModal.show();
                    } else {
                        alert("Gagal menyimpan: " + (data.message || ""));
                    }
                });
        });

        // Kembali ke monitor setelah submit
        document.getElementById('backMonitor').addEventListener('click', () => {
            successModal.hide();
            window.location.href = "monitor.php?plant=" + encodeURIComponent('<?= $plant ?>');
        });
    </script>

</body>

</html>