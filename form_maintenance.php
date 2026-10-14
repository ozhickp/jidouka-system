<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['machine_id'])) {
    header("Location: monitor.php");
    exit;
}

$machine_id = (int)$_GET['machine_id'];

/* =========================
   AMBIL DATA MACHINE
=========================*/
$stmt = $conn->prepare("SELECT * FROM machine WHERE id = ?");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$machine = $stmt->get_result()->fetch_assoc();

if (!$machine) {
    header("Location: monitor.php");
    exit;
}

$plant = $machine['plant'];

// =========================
// AUTO START MAINTENANCE
// =========================
if ($machine['status'] == 2 && $machine['repair_status'] == 'pending') {
    $user = $_SESSION['user']['name'];
    $plant_value = $machine['plant'];

    // INSERT maintenance_logs
    $stmt = $conn->prepare("INSERT INTO maintenance_logs (machine_id, plant, handled_by, waktu_mulai) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $machine_id, $plant_value, $user);
    $stmt->execute();
    $maintenance_id = $conn->insert_id;

    // UPDATE downtime_logs dengan maintenance_id & plant
    $stmt = $conn->prepare("UPDATE downtime_logs SET maintenance_logs_id = ?, plant = ? WHERE machine_id = ? AND downtime_end IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("isi", $maintenance_id, $plant_value, $machine_id);
    $stmt->execute();

    // UPDATE machine status
    $stmt = $conn->prepare("UPDATE machine SET status = 3, repair_status = 'progress' WHERE id = ?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT * FROM machine WHERE id = ?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $machine = $stmt->get_result()->fetch_assoc();
}

/* =========================
   KOMPRES FOTO DOKUMENTASI
   Foto dari HP bisa 3-10 MB per file. Sebelum disimpan, foto diperkecil
   (sisi terpanjang maks. IMG_MAX_SIDE px) lalu di-encode ulang sebagai JPEG
   kualitas IMG_QUALITY: biasanya jadi sekitar 200-500 KB, tetap tajam untuk
   dokumentasi, dan jauh lebih ringan saat di-embed ke export Excel.
   - Orientasi EXIF diterapkan lalu EXIF dibuang, jadi foto tidak miring di
     Excel / viewer yang mengabaikan EXIF.
   - PNG dikonversi ke JPEG (latar transparan jadi putih).
   - Foto yang sudah kecil & tegak disimpan apa adanya (tanpa kompres ulang).
   - Kalau ekstensi GD tidak ada / memori tidak cukup, foto disimpan apa
     adanya (perilaku lama), jadi upload tidak pernah gagal gara-gara kompresi.
   Ubah angka di bawah kalau ingin lebih tajam / lebih kecil.
=========================*/
const IMG_MAX_SIDE   = 1600;   // sisi terpanjang (px)
const IMG_QUALITY    = 80;     // kualitas JPEG 0-100
const IMG_SKIP_BYTES = 800000; // JPEG tegak sekecil ini (dan <= IMG_MAX_SIDE) tidak dikompres ulang

function iniBytes($v)
{
    $v = trim((string)$v);
    $n = (float)$v;
    $u = strtolower(substr($v, -1));
    if ($u === 'g') $n *= 1073741824;
    elseif ($u === 'm') $n *= 1048576;
    elseif ($u === 'k') $n *= 1024;
    return (int)$n;
}

// Cukup memori untuk mendecode gambar $w x $h? (estimasi: sumber + hasil/rotasi)
function imageMemoryOk($w, $h)
{
    $limit = iniBytes(ini_get('memory_limit'));
    if ($limit <= 0) return true; // -1 = tanpa batas
    $need = (int)($w * $h * 4 * 2.5);
    if (memory_get_usage(true) + $need <= $limit) return true;
    if ($limit < 536870912) { // coba naikkan ke 512M
        @ini_set('memory_limit', '512M');
        $limit = iniBytes(ini_get('memory_limit'));
        return $limit <= 0 || memory_get_usage(true) + $need <= $limit;
    }
    return false;
}

// Simpan foto upload ke $folder. Mengembalikan nama file yang tersimpan,
// atau '' kalau file bukan gambar JPG/PNG yang valid / gagal disimpan.
function saveCompressedImage($tmpPath, $folder, $prefix)
{
    $info = @getimagesize($tmpPath);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
        return '';
    }
    $isJpeg   = ($info['mime'] === 'image/jpeg');
    $w        = (int)$info[0];
    $h        = (int)$info[1];
    $origSize = (int)filesize($tmpPath);
    $baseName = $prefix . time();
    $keepName = $baseName . '.' . ($isJpeg ? 'jpg' : 'png');

    // Orientasi EXIF (hanya JPEG)
    $orientation = 1;
    if ($isJpeg && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmpPath);
        if (!empty($exif['Orientation'])) $orientation = (int)$exif['Orientation'];
    }

    $gdOk = function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng')
        && function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

    // Tidak bisa / tidak perlu dikompres -> simpan apa adanya
    $alreadyOk = $isJpeg && $origSize <= IMG_SKIP_BYTES && max($w, $h) <= IMG_MAX_SIDE && $orientation <= 1;
    if (!$gdOk || $alreadyOk || $w < 1 || $h < 1 || !imageMemoryOk($w, $h)) {
        return move_uploaded_file($tmpPath, $folder . $keepName) ? $keepName : '';
    }

    $src = $isJpeg ? @imagecreatefromjpeg($tmpPath) : @imagecreatefrompng($tmpPath);
    if (!$src) {
        return move_uploaded_file($tmpPath, $folder . $keepName) ? $keepName : '';
    }

    // Terapkan orientasi EXIF (imagerotate memutar berlawanan arah jarum jam)
    $angle = 0;
    if ($orientation === 3) $angle = 180;
    elseif ($orientation === 6) $angle = -90;
    elseif ($orientation === 8) $angle = 90;
    if ($angle !== 0 && function_exists('imagerotate')) {
        $rot = imagerotate($src, $angle, 0);
        if ($rot) {
            imagedestroy($src);
            $src = $rot;
            $w = imagesx($src);
            $h = imagesy($src);
        } else {
            $angle = 0;
        }
    } else {
        $angle = 0;
    }

    // Perkecil kalau melebihi IMG_MAX_SIDE
    $scale = min(1, IMG_MAX_SIDE / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih (PNG transparan)
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    $newName = $baseName . '.jpg';
    $newPath = $folder . $newName;
    $ok = imagejpeg($dst, $newPath, IMG_QUALITY);
    imagedestroy($dst);

    if (!$ok) {
        @unlink($newPath);
        return move_uploaded_file($tmpPath, $folder . $keepName) ? $keepName : '';
    }

    // Kalau hasilnya malah lebih besar dari aslinya (tanpa perubahan ukuran/rotasi), pakai aslinya
    if ($isJpeg && $angle === 0 && $scale >= 1 && (int)filesize($newPath) >= $origSize) {
        @unlink($newPath);
        return move_uploaded_file($tmpPath, $folder . $keepName) ? $keepName : '';
    }

    return $newName;
}

/* =========================
   AMBIL DATA ENGINE (POST atau GET)
=========================*/
$engine_type = $_POST['engine_type'] ?? ($_GET['engine_name'] ?? null);

// =========================
// SUBMIT FORM

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $perbaikan_type = $_POST['perbaikan_type'] ?? '';
    if (!in_array($perbaikan_type, ['engine', 'machine', 'both'])) {
        die("Pilihan perbaikan tidak valid.");
    }

    $kerusakan_machine = $_POST['kerusakan_machine'] ?? null;
    $perbaikan_machine = $_POST['perbaikan_machine'] ?? null;
    $engine_serial = $_POST['engine_serial'] ?? null;
    $kerusakan_engine = $_POST['kerusakan_engine'] ?? null;
    $perbaikan_engine = $_POST['perbaikan_engine'] ?? null;

    $namaFile_machine = "";
    $namaFile_engine = "";

    // Validasi required (semua field + foto wajib diisi sesuai jenis laporan)
    $needMachine = ($perbaikan_type == 'machine' || $perbaikan_type == 'both');
    $needEngine  = ($perbaikan_type == 'engine'  || $perbaikan_type == 'both');

    if ($needMachine) {
        if (empty(trim($kerusakan_machine ?? '')) || empty(trim($perbaikan_machine ?? ''))) {
            die("Problem dan Corrective Action untuk machine wajib diisi.");
        }
        if (!isset($_FILES['dokumentasi_machine']) || $_FILES['dokumentasi_machine']['error'] != 0) {
            die("Dokumentasi machine wajib diupload.");
        }
    }
    if ($needEngine) {
        if (
            empty(trim($engine_type ?? '')) || empty(trim($engine_serial ?? '')) ||
            empty(trim($kerusakan_engine ?? '')) || empty(trim($perbaikan_engine ?? ''))
        ) {
            die("Tipe mesin, Serial Number, Problem, dan Corrective Action untuk engine wajib diisi.");
        }
        if (!isset($_FILES['dokumentasi_engine']) || $_FILES['dokumentasi_engine']['error'] != 0) {
            die("Dokumentasi engine wajib diupload.");
        }
        // Tipe mesin harus benar-benar ada di master engine
        $stmt = $conn->prepare("SELECT id FROM engine WHERE engine_name = ? LIMIT 1");
        $stmt->bind_param("s", $engine_type);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            die("Tipe mesin tidak ditemukan. Pilih dari daftar pencarian.");
        }
    }

    // Upload FOTO MACHINE
    if (($perbaikan_type == 'machine' || $perbaikan_type == 'both') && isset($_FILES['dokumentasi_machine']) && $_FILES['dokumentasi_machine']['error'] == 0) {
        $folder = "uploads/machine/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        $ext = strtolower(pathinfo($_FILES['dokumentasi_machine']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['dokumentasi_machine']['size'] <= 10000000) {
            // Dikompres otomatis (lihat saveCompressedImage di atas)
            $namaFile_machine = saveCompressedImage($_FILES['dokumentasi_machine']['tmp_name'], $folder, "IMG_MACHINE_");
            if ($namaFile_machine === '') die("File dokumentasi machine bukan gambar JPG/PNG yang valid.");
        } else die("File dokumentasi machine harus JPG/PNG max 10MB.");
    }

    // Upload FOTO ENGINE
    if (($perbaikan_type == 'engine' || $perbaikan_type == 'both') && isset($_FILES['dokumentasi_engine']) && $_FILES['dokumentasi_engine']['error'] == 0) {
        $folder = "uploads/engine/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        $ext = strtolower(pathinfo($_FILES['dokumentasi_engine']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['dokumentasi_engine']['size'] <= 10000000) {
            // Dikompres otomatis (lihat saveCompressedImage di atas)
            $namaFile_engine = saveCompressedImage($_FILES['dokumentasi_engine']['tmp_name'], $folder, "IMG_ENGINE_");
            if ($namaFile_engine === '') die("File dokumentasi engine bukan gambar JPG/PNG yang valid.");
        } else die("File dokumentasi engine harus JPG/PNG max 10MB.");
    }

    // AMBIL ENGINE_ID
    $engine_id = null;
    if (!empty($engine_type)) {
        $stmt = $conn->prepare("SELECT id FROM engine WHERE engine_name = ? LIMIT 1");
        $stmt->bind_param("s", $engine_type);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) $engine_id = $res['id'];
    }

    // UPDATE MAINTENANCE LOG
    if ($engine_id !== null) {
        $stmt = $conn->prepare("
            UPDATE maintenance_logs
            SET kerusakan_machine=?, perbaikan_machine=?, dokumentasi_machine=?,
                kerusakan_engine=?, perbaikan_engine=?, dokumentasi_engine=?,
                engine_id=?, engine_serial=?, waktu_selesai=NOW()
            WHERE machine_id=? AND waktu_selesai IS NULL
        ");
        $stmt->bind_param(
            "ssssssisi",
            $kerusakan_machine,
            $perbaikan_machine,
            $namaFile_machine,
            $kerusakan_engine,
            $perbaikan_engine,
            $namaFile_engine,
            $engine_id,
            $engine_serial,
            $machine_id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE maintenance_logs
            SET kerusakan_machine=?, perbaikan_machine=?, dokumentasi_machine=?,
                kerusakan_engine=?, perbaikan_engine=?, dokumentasi_engine=?,
                engine_id=NULL, engine_serial=?, waktu_selesai=NOW()
            WHERE machine_id=? AND waktu_selesai IS NULL
        ");
        $stmt->bind_param(
            "sssssssi",
            $kerusakan_machine,
            $perbaikan_machine,
            $namaFile_machine,
            $kerusakan_engine,
            $perbaikan_engine,
            $namaFile_engine,
            $engine_serial,
            $machine_id
        );
    }
    $stmt->execute();

    // UPDATE STATUS MACHINE
    $stmt = $conn->prepare("UPDATE machine SET status=1, repair_status='done' WHERE id=?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    // UPDATE DOWNTIME LOG
    $stmt = $conn->prepare("
        UPDATE downtime_logs dl
        JOIN machine m ON dl.machine_id=m.id
        SET dl.downtime_end=NOW(), dl.plant=m.plant
        WHERE dl.machine_id=? AND dl.downtime_end IS NULL
        ORDER BY dl.id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    header("Location: monitor.php");
    exit;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Form Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .engine-form,
        .machine-form {
            display: none;
        }

        #engine_suggestions {
            position: absolute;
            z-index: 1000;
            width: 100%;
        }

        /* Modal kamera */
        .cam-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(15, 23, 42, .8);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .cam-modal.open {
            display: flex;
        }

        .cam-box {
            background: #fff;
            border-radius: .5rem;
            width: 100%;
            max-width: 720px;
            overflow: hidden;
        }

        .cam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .75rem 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .cam-body {
            background: #000;
            position: relative;
            min-height: 120px;
        }

        #camVideo {
            width: 100%;
            max-height: 65vh;
            display: block;
            background: #000;
        }

        #camError {
            color: #fff;
            padding: 1rem;
            text-align: center;
        }

        .cam-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: center;
            padding: .75rem 1rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-warning">
                <h4>Form Maintenance</h4>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="maintenanceForm">

                    <div class="mb-3">
                        <label>Machine Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($machine['machine_name']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Plant</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($plant); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <input type="text" class="form-control" value="MAINTENANCE" readonly>
                    </div>

                    <!-- Toggle Buttons -->
                    <div class="mb-3">
                        <label>Jenis Laporan Perbaikan</label><br>
                        <div class="btn-group me-2" role="group">
                            <input type="radio" class="btn-check" name="perbaikan_type" id="type_engine" value="engine" autocomplete="off">
                            <label class="btn btn-outline-primary" for="type_engine">Engine</label>

                            <input type="radio" class="btn-check" name="perbaikan_type" id="type_machine" value="machine" autocomplete="off">
                            <label class="btn btn-outline-success" for="type_machine">Machine/Tools</label>

                            <input type="radio" class="btn-check" name="perbaikan_type" id="type_both" value="both" autocomplete="off">
                            <label class="btn btn-outline-warning" for="type_both">Keduanya</label>
                        </div>
                        <a href="engine_guide.php?machine_id=<?= $machine_id ?>" target="_blank" class="btn btn-sm btn-info">Engine Guide</a>
                    </div>

                    <!-- Engine Form -->
                    <div class="engine-form">
                        <div class="mb-3">
                            <label>Tipe Mesin (Cari & Pilih)</label>
                            <input type="text" class="form-control" id="engine_type" name="engine_type" autocomplete="off" placeholder="Cari tipe mesin..." value="<?= htmlspecialchars($engine_type ?? ''); ?>">
                            <div id="engine_suggestions" class="list-group"></div>
                        </div>
                        <div class="mb-3">
                            <label>Serial Number</label>
                            <input type="text" class="form-control" name="engine_serial" placeholder="Masukkan serial number">
                        </div>
                        <div class="mb-3">
                            <label>Problem (Engine)</label>
                            <textarea name="kerusakan_engine" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Corrective Action (Engine)</label>
                            <textarea name="perbaikan_engine" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Documentation (Engine, Max 10MB, JPG/PNG)</label>
                            <div class="input-group">
                                <input type="file" name="dokumentasi_engine" id="dokumentasi_engine" class="form-control" accept="image/jpeg,image/png">
                                <button type="button" class="btn btn-outline-primary btn-camera" data-target="dokumentasi_engine" data-title="Documentation">Ambil dari Kamera</button>
                            </div>
                            <div class="mt-2 d-none" id="preview_dokumentasi_engine">
                                <img class="img-thumbnail" style="max-height:180px" alt="Preview">
                            </div>
                        </div>
                    </div>

                    <!-- Machine Form -->
                    <div class="machine-form">
                        <div class="mb-3">
                            <label>Problem (Machine/Tools)</label>
                            <textarea name="kerusakan_machine" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Corrective Action (Machine/Tools)</label>
                            <textarea name="perbaikan_machine" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Documentation (Machine/Tools, Max 10MB, JPG/PNG)</label>
                            <div class="input-group">
                                <input type="file" name="dokumentasi_machine" id="dokumentasi_machine" class="form-control" accept="image/jpeg,image/png">
                                <button type="button" class="btn btn-outline-primary btn-camera" data-target="dokumentasi_machine" data-title="Documentation">Ambil dari Kamera</button>
                            </div>
                            <div class="mt-2 d-none" id="preview_dokumentasi_machine">
                                <img class="img-thumbnail" style="max-height:180px" alt="Preview">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100" id="submitBtn" disabled>Submit Maintenance</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Kamera -->
    <div id="cameraModal" class="cam-modal">
        <div class="cam-box">
            <div class="cam-header">
                <strong id="camTitle">Ambil Foto</strong>
                <button type="button" class="btn-close" id="camClose" aria-label="Tutup"></button>
            </div>
            <div class="cam-body">
                <video id="camVideo" playsinline autoplay muted></video>
                <div id="camError" class="d-none"></div>
            </div>
            <canvas id="camCanvas" class="d-none"></canvas>
            <div class="cam-actions">
                <button type="button" class="btn btn-outline-secondary" id="camSwitch">Ganti Kamera (Depan/Belakang)</button>
                <button type="button" class="btn btn-success" id="camCapture">Ambil Foto</button>
                <button type="button" class="btn btn-outline-danger" id="camCancel">Batal</button>
            </div>
        </div>
    </div>

    <script>
        const radios = document.querySelectorAll('input[name="perbaikan_type"]');
        const engineForm = document.querySelector('.engine-form');
        const machineForm = document.querySelector('.machine-form');
        const submitBtn = document.getElementById('submitBtn');

        // true kalau tipe mesin dipilih dari daftar (atau sudah terisi dari ?engine_name=)
        let engineValid = engineInputHasValue();

        function engineInputHasValue() {
            const el = document.getElementById('engine_type');
            return !!(el && el.value.trim());
        }

        // Semua field yang tampil (sesuai jenis laporan) wajib terisi, termasuk foto
        function validateForm() {
            const type = document.querySelector('input[name="perbaikan_type"]:checked')?.value;
            if (!type) {
                submitBtn.disabled = true;
                return;
            }

            const scopes = [];
            if (type === 'engine' || type === 'both') scopes.push(engineForm);
            if (type === 'machine' || type === 'both') scopes.push(machineForm);

            let valid = true;
            scopes.forEach(scope => {
                scope.querySelectorAll('input:not([type="hidden"]), textarea').forEach(el => {
                    if (el.type === 'file') {
                        if (el.files.length === 0 || el.dataset.busy === '1') valid = false; // busy = foto sedang dikompres
                    } else if (!el.value.trim()) {
                        valid = false;
                    }
                });
            });
            if ((type === 'engine' || type === 'both') && !engineValid) valid = false;

            submitBtn.disabled = !valid;
        }
        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                const val = document.querySelector('input[name="perbaikan_type"]:checked').value;
                engineForm.style.display = (val === 'engine' || val === 'both') ? 'block' : 'none';
                machineForm.style.display = (val === 'machine' || val === 'both') ? 'block' : 'none';
                validateForm();
            });
        });
        document.querySelectorAll('input, textarea').forEach(el => el.addEventListener('input', validateForm));
        document.querySelectorAll('input[type="file"]').forEach(el => el.addEventListener('change', validateForm));


        // =========================
        // Preview foto (dari file/galeri maupun kamera)
        // =========================
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', () => {
                const wrap = document.getElementById('preview_' + input.id);
                const img = wrap.querySelector('img');
                if (img.dataset.url) URL.revokeObjectURL(img.dataset.url);
                const file = input.files[0];
                if (!file) {
                    wrap.classList.add('d-none');
                    return;
                }
                const url = URL.createObjectURL(file);
                img.src = url;
                img.dataset.url = url;
                wrap.classList.remove('d-none');
            });
        });

        // =========================
        // Kompres foto otomatis sebelum diupload (galeri maupun kamera)
        // Sisi terpanjang dibatasi IMG_MAX_SIDE px, JPEG kualitas IMG_QUALITY.
        // Hasilnya dimasukkan kembali ke input file yang sama, jadi proses
        // submit tidak berubah. Server tetap memeriksa/mengompres ulang kalau perlu.
        // =========================
        const IMG_MAX_SIDE = 1600;
        const IMG_QUALITY = 0.8;

        async function compressImageFile(file) {
            if (!file || !/^image\/(jpeg|png)$/.test(file.type)) return file;
            let bitmap;
            try {
                // 'from-image' = foto HP yang diputar via EXIF tetap tegak
                bitmap = await createImageBitmap(file, {
                    imageOrientation: 'from-image'
                });
            } catch (e) {
                try {
                    bitmap = await createImageBitmap(file);
                } catch (e2) {
                    return file; // browser lama: upload apa adanya, server yang mengompres
                }
            }
            const scale = Math.min(1, IMG_MAX_SIDE / Math.max(bitmap.width, bitmap.height));
            const cw = Math.max(1, Math.round(bitmap.width * scale));
            const ch = Math.max(1, Math.round(bitmap.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = cw;
            canvas.height = ch;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff'; // latar putih untuk PNG transparan
            ctx.fillRect(0, 0, cw, ch);
            ctx.drawImage(bitmap, 0, 0, cw, ch);
            if (bitmap.close) bitmap.close();
            const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', IMG_QUALITY));
            if (!blob) return file;
            // Kalau tidak diperkecil & hasilnya tidak lebih kecil, pakai file asli
            if (scale >= 1 && file.type === 'image/jpeg' && blob.size >= file.size) return file;
            const base = file.name.replace(/\.[^.]+$/, '');
            return new File([blob], 'cmp_' + base + '.jpg', {
                type: 'image/jpeg'
            });
        }

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', async () => {
                const file = input.files[0];
                // foto dari kamera sudah dikecilkan saat diambil; hasil kompres tidak diproses ulang
                if (!file || file.name.startsWith('camera_') || file.name.startsWith('cmp_')) return;
                input.dataset.busy = '1';
                validateForm();
                try {
                    const small = await compressImageFile(file);
                    if (small !== file && input.files[0] === file) { // pengguna belum ganti file lain
                        const dt = new DataTransfer();
                        dt.items.add(small);
                        input.files = dt.files;
                    }
                } catch (e) {
                    /* gagal kompres: tetap pakai file asli */
                } finally {
                    delete input.dataset.busy;
                    validateForm();
                }
            });
        });

        // =========================
        // Kamera (getUserMedia) - depan/belakang
        // Hasil foto dimasukkan ke input file yang sama, jadi proses upload tidak berubah.
        // =========================
        const camModal = document.getElementById('cameraModal');
        const camVideo = document.getElementById('camVideo');
        const camCanvas = document.getElementById('camCanvas');
        const camError = document.getElementById('camError');
        const camSwitchBtn = document.getElementById('camSwitch');
        const camCaptureBtn = document.getElementById('camCapture');
        let camStream = null;
        let camTarget = null;
        let camFacing = 'environment'; // mulai dari kamera belakang
        let camDeviceId = null; // dipakai kalau perangkat tidak melaporkan facingMode (mis. PC dengan >1 webcam)

        function stopCamera() {
            if (camStream) {
                camStream.getTracks().forEach(t => t.stop());
                camStream = null;
            }
            camVideo.srcObject = null;
        }

        function showCamError(msg) {
            camError.textContent = msg;
            camError.classList.remove('d-none');
            camVideo.classList.add('d-none');
            camCaptureBtn.disabled = true;
            camSwitchBtn.classList.add('d-none');
        }

        async function startCamera() {
            stopCamera();
            camError.classList.add('d-none');
            camVideo.classList.remove('d-none');
            camCaptureBtn.disabled = false;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showCamError('Browser tidak mendukung akses kamera (halaman harus dibuka lewat HTTPS atau localhost).');
                return;
            }
            const video = {
                width: {
                    ideal: 1920
                },
                height: {
                    ideal: 1080
                }
            };
            if (camDeviceId) video.deviceId = {
                exact: camDeviceId
            };
            else video.facingMode = {
                ideal: camFacing
            };

            try {
                camStream = await navigator.mediaDevices.getUserMedia({
                    video,
                    audio: false
                });
                camVideo.srcObject = camStream;
                const settings = camStream.getVideoTracks()[0].getSettings();
                // Cermin preview hanya untuk kamera depan (hasil foto tidak dicermin)
                camVideo.style.transform = settings.facingMode === 'user' ? 'scaleX(-1)' : 'none';
                // Tombol ganti kamera hanya tampil kalau ada lebih dari 1 kamera
                const devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
                camSwitchBtn.classList.toggle('d-none', devices.length < 2);
            } catch (err) {
                console.error('Camera error:', err);
                showCamError('Tidak bisa mengakses kamera. Pastikan izin kamera sudah diberikan dan kamera tidak dipakai aplikasi lain.');
            }
        }

        async function switchCamera() {
            const track = camStream && camStream.getVideoTracks()[0];
            if (!track) return;
            if (track.getSettings().facingMode) {
                // HP/tablet: tukar depan <-> belakang
                camFacing = camFacing === 'user' ? 'environment' : 'user';
                camDeviceId = null;
            } else {
                // PC/laptop: putar antar webcam yang tersedia
                const devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
                if (devices.length < 2) return;
                const current = track.getSettings().deviceId;
                const idx = devices.findIndex(d => d.deviceId === current);
                camDeviceId = devices[(idx + 1) % devices.length].deviceId;
            }
            startCamera();
        }

        function openCamera(targetId, title) {
            camTarget = targetId;
            document.getElementById('camTitle').textContent = 'Ambil Foto - ' + title;
            camModal.classList.add('open');
            startCamera();
        }

        function closeCamera() {
            stopCamera();
            camModal.classList.remove('open');
            camTarget = null;
        }

        function capturePhoto() {
            const w = camVideo.videoWidth;
            const h = camVideo.videoHeight;
            if (!w || !h || !camTarget) return;
            const camScale = Math.min(1, IMG_MAX_SIDE / Math.max(w, h));
            const cw = Math.max(1, Math.round(w * camScale));
            const ch = Math.max(1, Math.round(h * camScale));
            camCanvas.width = cw;
            camCanvas.height = ch;
            camCanvas.getContext('2d').drawImage(camVideo, 0, 0, cw, ch);
            const targetId = camTarget;
            camCanvas.toBlob(blob => {
                if (!blob) return;
                const file = new File([blob], 'camera_' + Date.now() + '.jpg', {
                    type: 'image/jpeg'
                });
                const dt = new DataTransfer();
                dt.items.add(file);
                const input = document.getElementById(targetId);
                input.files = dt.files;
                input.dispatchEvent(new Event('change', {
                    bubbles: true
                })); // memicu validasi & preview
                closeCamera();
            }, 'image/jpeg', IMG_QUALITY);
        }

        document.querySelectorAll('.btn-camera').forEach(btn => {
            btn.addEventListener('click', () => openCamera(btn.dataset.target, btn.dataset.title));
        });
        camSwitchBtn.addEventListener('click', switchCamera);
        camCaptureBtn.addEventListener('click', capturePhoto);
        document.getElementById('camCancel').addEventListener('click', closeCamera);
        document.getElementById('camClose').addEventListener('click', closeCamera);
        camModal.addEventListener('click', e => {
            if (e.target === camModal) closeCamera();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && camModal.classList.contains('open')) closeCamera();
        });
        window.addEventListener('pagehide', stopCamera);

        // Autocomplete engine
        const engineInput = document.getElementById('engine_type');
        const suggestionBox = document.getElementById('engine_suggestions');
        engineInput.addEventListener('input', () => {
            engineValid = false; // diketik manual = belum valid sampai dipilih dari daftar
            validateForm();
            const query = engineInput.value.trim();
            if (query.length < 1) {
                suggestionBox.innerHTML = '';
                return;
            }
            fetch('search_engine.php?q=' + encodeURIComponent(query))
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
                                engineValid = true;
                                suggestionBox.innerHTML = '';
                                validateForm();
                            });
                            suggestionBox.appendChild(div);
                        });
                    }
                }).catch(() => {
                    suggestionBox.innerHTML = '';
                });
        });
        document.addEventListener('click', e => {
            if (!engineInput.contains(e.target)) suggestionBox.innerHTML = '';
        });
    </script>
</body> 

</html>