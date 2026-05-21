<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_receiver'])) {
    $type = $_POST['type'];
    $contact = trim($_POST['contact']);

    if ($type == 'gmail' || $type == 'outlook') {
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("INSERT IGNORE INTO notification_settings (type,email) VALUES (?, ?)");
            $stmt->bind_param("ss", $type, $contact);
            $stmt->execute();
        }
    } elseif ($type == 'whatsapp') {
        if (preg_match('/^0[0-9]{8,14}$/', $contact)) {
            $contact = '62' . substr($contact, 1);
        }
        if (preg_match('/^\+?[0-9]{8,15}$/', $contact)) {
            $stmt = $conn->prepare("INSERT IGNORE INTO notification_settings (type,email) VALUES (?, ?)");
            $stmt->bind_param("ss", $type, $contact);
            $stmt->execute();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM notification_settings WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
}

$result = mysqli_query($conn, "SELECT * FROM notification_settings ORDER BY type, id DESC");
$receivers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $receivers[$row['type']][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notification Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        table td:last-child {
            border-left: 0 !important;
        }
        .type-header {
            background: #f1f1f1;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📧 Notification Settings</h5>
            <a href="settings.php" class="btn btn-secondary btn-sm">⬅ Back</a>
        </div>
        <div class="card-body">

            <h6 class="mb-3 text-dark fw-semibold">Tambahkan Penerima</h6>
            <form method="POST" class="mb-4 d-flex gap-2">
                <select name="type" class="form-select" required>
                    <option value="gmail">Gmail</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="outlook">Outlook</option>
                </select>
                <input type="text" name="contact" class="form-control" placeholder="Masukkan email/nomor..." required>
                <button class="btn btn-success" name="add_receiver">Tambah</button>
            </form>

            <?php foreach (['gmail','whatsapp','outlook'] as $type): ?>
                <?php if(!empty($receivers[$type])): ?>
                    <h6 class="mb-2 text-dark fw-semibold"><?= strtoupper($type) ?></h6>
                    <table class="table table-bordered align-middle mb-4">
                        <tbody>
                        <?php foreach ($receivers[$type] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td width="80" class="text-center">
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id']; ?>">Hapus</button>

                                    <div class="modal fade" id="deleteModal<?= $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel<?= $row['id']; ?>">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Yakin ingin menghapus <b><?= htmlspecialchars($row['email']); ?></b>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                                        <button class="btn btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endforeach; ?>

        </div>
    </div>
</div>

</body>
</html>