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

    // Validasi required
    if (($perbaikan_type == 'machine' || $perbaikan_type == 'both') && (empty(trim($kerusakan_machine)) || empty(trim($perbaikan_machine)))) {
        die("Problem dan Corrective Action untuk machine wajib diisi.");
    }
    if (($perbaikan_type == 'engine' || $perbaikan_type == 'both') && (empty(trim($engine_type)) || empty(trim($kerusakan_engine)) || empty(trim($perbaikan_engine)))) {
        die("Tipe mesin, Problem, dan Corrective Action untuk engine wajib diisi.");
    }

    // Upload FOTO MACHINE
    if (($perbaikan_type == 'machine' || $perbaikan_type == 'both') && isset($_FILES['dokumentasi_machine']) && $_FILES['dokumentasi_machine']['error'] == 0) {
        $folder = "uploads/machine/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        $ext = strtolower(pathinfo($_FILES['dokumentasi_machine']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png']) && $_FILES['dokumentasi_machine']['size'] <= 10000000) {
            $namaFile_machine = "IMG_MACHINE_".time().".".$ext;
            move_uploaded_file($_FILES['dokumentasi_machine']['tmp_name'],$folder.$namaFile_machine);
        } else die("File dokumentasi machine harus JPG/PNG max 10MB.");
    }

    // Upload FOTO ENGINE
    if (($perbaikan_type == 'engine' || $perbaikan_type == 'both') && isset($_FILES['dokumentasi_engine']) && $_FILES['dokumentasi_engine']['error']==0) {
        $folder = "uploads/engine/";
        if (!is_dir($folder)) mkdir($folder,0777,true);
        $ext = strtolower(pathinfo($_FILES['dokumentasi_engine']['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png']) && $_FILES['dokumentasi_engine']['size']<=10000000) {
            $namaFile_engine = "IMG_ENGINE_".time().".".$ext;
            move_uploaded_file($_FILES['dokumentasi_engine']['tmp_name'],$folder.$namaFile_engine);
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
        $stmt->bind_param("ssssssisi",$kerusakan_machine,$perbaikan_machine,$namaFile_machine,
            $kerusakan_engine,$perbaikan_engine,$namaFile_engine,
            $engine_id,$engine_serial,$machine_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE maintenance_logs
            SET kerusakan_machine=?, perbaikan_machine=?, dokumentasi_machine=?,
                kerusakan_engine=?, perbaikan_engine=?, dokumentasi_engine=?,
                engine_id=NULL, engine_serial=?, waktu_selesai=NOW()
            WHERE machine_id=? AND waktu_selesai IS NULL
        ");
        $stmt->bind_param("sssssssi",$kerusakan_machine,$perbaikan_machine,$namaFile_machine,
            $kerusakan_engine,$perbaikan_engine,$namaFile_engine,
            $engine_serial,$machine_id);
    }
    $stmt->execute();

    // UPDATE STATUS MACHINE
    $stmt = $conn->prepare("UPDATE machine SET status=1, repair_status='done' WHERE id=?");
    $stmt->bind_param("i",$machine_id);
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
    $stmt->bind_param("i",$machine_id);
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
        .engine-form, .machine-form {display:none;}
        #engine_suggestions {position:absolute; z-index:1000; width:100%;}
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
                    <input type="text" class="form-control" value="<?=htmlspecialchars($machine['machine_name']);?>" readonly>
                </div>
                <div class="mb-3">
                    <label>Plant</label>
                    <input type="text" class="form-control" value="<?=htmlspecialchars($plant);?>" readonly>
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
                        <input type="text" class="form-control" id="engine_type" name="engine_type" autocomplete="off" placeholder="Cari tipe mesin..." value="<?=htmlspecialchars($engine_type ?? '');?>">
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
                        <input type="file" name="dokumentasi_engine" class="form-control" accept="image/jpeg,image/png">
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
                        <input type="file" name="dokumentasi_machine" class="form-control" accept="image/jpeg,image/png">
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100" id="submitBtn" disabled>Submit Maintenance</button>
            </form>
        </div>
    </div>
</div>

<script>
const radios = document.querySelectorAll('input[name="perbaikan_type"]');
const engineForm = document.querySelector('.engine-form');
const machineForm = document.querySelector('.machine-form');
const submitBtn = document.getElementById('submitBtn');

function validateForm(){
    const type = document.querySelector('input[name="perbaikan_type"]:checked')?.value;
    if(!type){ submitBtn.disabled=true; return; }
    let valid=true;
    if(type==='engine'||type==='both'){
        document.querySelectorAll('.engine-form textarea, .engine-form input[type="text"]').forEach(el=>{
            if(!el.value.trim()) valid=false;
        });
    }
    if(type==='machine'||type==='both'){
        document.querySelectorAll('.machine-form textarea, .machine-form input[type="file"]').forEach(el=>{
            if((el.type==='file' && el.files.length===0)|| (el.type!=='file' && !el.value.trim())) valid=false;
        });
    }
    submitBtn.disabled=!valid;
}
radios.forEach(radio=>{ radio.addEventListener('change',()=>{
    const val=document.querySelector('input[name="perbaikan_type"]:checked').value;
    engineForm.style.display=(val==='engine'||val==='both')?'block':'none';
    machineForm.style.display=(val==='machine'||val==='both')?'block':'none';
    validateForm();
});});
document.querySelectorAll('input, textarea').forEach(el=>el.addEventListener('input',validateForm));
document.querySelectorAll('input[type="file"]').forEach(el=>el.addEventListener('change',validateForm));

// Autocomplete engine
const engineInput = document.getElementById('engine_type');
const suggestionBox = document.getElementById('engine_suggestions');
engineInput.addEventListener('input',()=>{
    const query=engineInput.value.trim();
    if(query.length<1){ suggestionBox.innerHTML=''; return;}
    fetch('search_engine.php?q='+encodeURIComponent(query))
    .then(res=>res.json())
    .then(data=>{
        suggestionBox.innerHTML='';
        if(data.length===0){
            const div=document.createElement('div');
            div.classList.add('list-group-item','text-muted');
            div.textContent='Tidak ada tipe';
            suggestionBox.appendChild(div);
        } else {
            data.forEach(item=>{
                const div=document.createElement('a');
                div.href='#';
                div.classList.add('list-group-item','list-group-item-action');
                div.textContent=item.engine_name;
                div.addEventListener('click',e=>{
                    e.preventDefault();
                    engineInput.value=item.engine_name;
                    suggestionBox.innerHTML='';
                    validateForm();
                });
                suggestionBox.appendChild(div);
            });
        }
    }).catch(()=>{suggestionBox.innerHTML='';});
});
document.addEventListener('click',e=>{if(!engineInput.contains(e.target)) suggestionBox.innerHTML='';});
</script>
</body>
</html>