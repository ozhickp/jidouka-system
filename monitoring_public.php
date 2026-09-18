<?php include "config.php"; ?>
<!DOCTYPE html>
<html>

<head>
    <title>Machine Monitoring</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
            background: #1a1d21;
            color: white;
        }

        /* ── LAYOUT UTAMA ── */
        .wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* ── HEADER ── */
        .header {
            background: #343a40;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            border-bottom: 2px solid #444;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .back-btn {
            padding: 6px 14px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        #clock {
            text-align: right;
            line-height: 1.3;
            min-width: 110px;
        }

        #clock .clock-date {
            font-size: 20px;
            /* font-weight: bold; */
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        #clock .clock-time {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        /* ── PLANT COLUMNS ── */
        .plants-row {
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 10px;
            overflow: hidden;
            min-height: 0;
        }

        .plant-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #23272b;
            border-radius: 12px;
            overflow: hidden;
            min-width: 0;
        }

        /* ── PLANT HEADER ── */
        .plant-header {
            padding: 10px 14px;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .col-assembly .plant-header {
            background: #1a73e8;
        }

        .col-testrun .plant-header {
            background: #7b2d8b;
        }

        .col-packing .plant-header {
            background: #e67e22;
        }

        .plant-summary {
            font-size: 11px;
            font-weight: normal;
            background: rgba(255, 255, 255, .2);
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0;
            white-space: nowrap;
        }

        /* ── TOMBOL ALARM DI SAMPING NAMA LINE ── */
        .plant-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .plant-title {
            white-space: nowrap;
        }

        .plant-alarm-btn {
            position: relative;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, .18);
            color: #fff;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .2s;
        }

        .plant-alarm-btn:hover {
            background: rgba(255, 255, 255, .32);
        }

        .plant-alarm-btn.alarm-active {
            background: #dc3545;
            animation: alarmPulseRing 1s infinite;
        }

        .plant-alarm-btn.alarm-muted {
            background: #6c757d;
        }

        .badge-count-mini {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ffc107;
            color: #1a1d21;
            font-size: 9px;
            font-weight: bold;
            border-radius: 50%;
            min-width: 15px;
            height: 15px;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0 2px;
            line-height: 1;
        }

        @keyframes alarmPulseRing {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, .7);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
            }
        }

        /* ── STRIP INFO MESIN STOP DI BAWAH NAMA LINE ── */
        .plant-alert-strip {
            background: #4a1a1a;
            color: #ff8a8a;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            padding: 4px 10px;
            letter-spacing: .3px;
            flex-shrink: 0;
            animation: alertStripBlink 1.4s infinite;
        }

        @keyframes alertStripBlink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .55;
            }
        }

        /* ── MACHINE GRID (di dalam kolom) ── */
        .machine_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            grid-auto-rows: minmax(0, 1fr);
            gap: 6px;
            padding: 8px;
            overflow: hidden;
            /* tidak scroll */
            align-content: stretch;
            flex: 1;
            min-height: 0;
        }

        /* Assembly punya 13 mesin dengan nama yang lebih panjang →
           3 kolom (bukan 4) supaya tiap kartu punya ruang lebih lebar */
        .col-assembly .machine_grid {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Assembly punya paling banyak baris (5 baris) sehingga tiap
           kartu jadi lebih pipih dibanding kolom lain - kecilkan
           sedikit teks & rapatkan spasi khusus di kolom ini supaya
           nama yang 2 baris (Process 10, 11, 13, dst) tidak mepet/
           terpotong oleh badge status di bawahnya */
        .col-assembly .machine_card {
            padding: 4px 6px;
        }

        .col-assembly .machine-name {
            font-size: 10.5px;
            line-height: 1.15;
            margin-bottom: 3px;
            /* selalu setinggi 2 baris walau namanya cuma 1 baris */
            min-height: 2.3em;
        }

        .col-assembly .machine-head {
            margin-bottom: 3px;
        }

        .col-assembly .status-badge {
            font-size: 10px;
            padding: 1px 6px;
        }

        .col-assembly .maintenance_status {
            font-size: 9px;
            margin-top: 2px;
            padding: 1px 5px;
        }

        /* Begitu strip notifikasi di bawah nama line muncul, dia "mencuri"
           sedikit tinggi dari grid kartu di bawahnya (grid ikut turun/
           menyempit). Class .has-alert ditempel via JS barengan strip-nya
           tampil, dan di sini dipakai buat mengecilkan padding/margin
           kartu persis secukupnya, supaya seluruh isi kartu (nama, badge,
           timer, status maintenance) tetap muat & tidak ada yang
           kepotong walau tingginya sedikit berkurang. */
        .plant-col.has-alert .machine_card {
            padding: 5px 8px;
        }

        .plant-col.has-alert .machine-name {
            margin-bottom: 2px;
        }

        .plant-col.has-alert .machine-head {
            margin-bottom: 2px;
        }

        .plant-col.has-alert .maintenance_status {
            margin-top: 2px;
        }

        .plant-col.has-alert .abnormal_time {
            margin-top: 2px;
            padding: 1px 6px;
        }

        .col-assembly.has-alert .machine_card {
            padding: 3px 6px;
        }

        .col-assembly.has-alert .machine-name {
            font-size: 10px;
            line-height: 1.05;
            margin-bottom: 1px;
            min-height: 2.1em;
        }

        .col-assembly.has-alert .machine-head {
            margin-bottom: 1px;
        }

        .col-assembly.has-alert .maintenance_status {
            margin-top: 1px;
            padding: 1px 4px;
        }

        .col-assembly.has-alert .abnormal_time {
            margin-top: 1px;
            padding: 1px 4px;
        }

        /* ── MACHINE CARD ── */
        .machine_card {
            position: relative;
            background: #2c3136;
            border-radius: 8px;
            padding: 6px 8px;
            text-align: center;
            border: 2px solid #444;
            transition: border-color .3s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            min-height: 0;
        }

        /* Baris atas kartu: nama mesin + tombol lonceng (khusus kartu
           STOPPED). Tombol lonceng ditaruh DI DALAM flow (bukan
           position:absolute menumpuk di pojok kartu), jadi dia tidak
           mungkin menindih badge STOPPED / timer walau nama mesinnya
           cuma 1 baris. Lebar yang dipakai lonceng sama seperti dulu
           (16px + 3px jarak), jadi ruang untuk nama tidak menyempit. */
        .machine-head {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            flex-shrink: 0;
            margin-bottom: 4px;
        }

        .machine_card .machine-head>.machine-name {
            flex: 1 1 auto;
            min-width: 0;
            margin-bottom: 0;
        }

        .machine-head>.machine-alarm-btn {
            position: static;
            top: auto;
            right: auto;
            flex: 0 0 16px;
        }

        /* ── TOMBOL ALARM DI MACHINE CARD ── */
        .machine-alarm-btn {
            position: absolute;
            top: 3px;
            right: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, .12);
            color: #ccc;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            padding: 0;
            line-height: 1;
        }

        .machine-alarm-btn.alarm-active {
            background: #dc3545;
            color: #fff;
            animation: alarmPulseSmall 1s infinite;
        }

        .machine-alarm-btn.alarm-muted {
            background: #6c757d;
            color: #fff;
        }

        .machine-alarm-btn.alarm-idle {
            background: rgba(252, 211, 77, .25);
            color: #fcd34d;
        }

        @keyframes alarmPulseSmall {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, .7);
            }

            50% {
                box-shadow: 0 0 0 5px rgba(220, 53, 69, 0);
            }
        }

        .machine_card.status-running {
            border-color: #28a745;
        }

        .machine_card.status-abnormal {
            border-color: #dc3545;
            background: #3a1f1f;
        }

        .machine-name {
            font-size: 12px;
            font-weight: bold;
            color: #e9ecef;
            margin-bottom: 4px;
            white-space: normal;
            word-wrap: break-word;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.2;
            flex-shrink: 0;
            /* Dikunci setinggi 2 baris (sesuai line-clamp) supaya tinggi
               total isi kartu sama antara mesin yang namanya 1 baris
               (Process 1 - Body) dan 2 baris (Process 3 - Radiator).
               Dengan begitu posisi badge/timer/status sejajar di semua
               kartu dan isi kartu selalu pas, running maupun stopped. */
            min-height: 2.4em;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }

        .status-badge.running {
            background: #28a745;
        }

        .status-badge.abnormal {
            background: #dc3545;
        }

        .maintenance_status {
            margin-top: 3px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 5px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .maint-clear {
            background: #1a4731;
            color: #6ee7a0;
        }

        .maint-pending {
            background: #4a1a1a;
            color: #f87171;
        }

        .maint-progress {
            background: #4a3500;
            color: #fcd34d;
        }

        .abnormal_time {
            margin-top: 3px;
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            background: rgba(0, 0, 0, 0.25);
            border-radius: 4px;
            padding: 2px 6px;
            letter-spacing: 1px;
            font-family: monospace;
            flex-shrink: 0;
        }

        /* Kolom Assembly kartunya paling sempit (3 kolom x 5 baris),
           jadi timer stopwatch-nya dibuat lebih ringkas biar seluruh
           isi kartu (nama + badge + timer + status maintenance)
           tetap muat tanpa terpotong */
        .col-assembly .abnormal_time {
            font-size: 11px;
            padding: 1px 5px;
            margin-top: 2px;
        }

        /* ── CONVEYOR STATUS ── */
        .conveyor-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .conveyor-status.conv-running {
            background: #28a745;
            color: #ffffff;
            border: none;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .conveyor-status.conv-stopped {
            background: #dc3545;
            color: #ffffff;
            border: none;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .conv-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .conv-running .conv-dot {
            background: #28a745;
        }

        .conv-stopped .conv-dot {
            background: #dc3545;
            animation: blink 1s infinite;
        }

        /* ── REFRESH INDICATOR ── */
        .footer {
            text-align: center;
            padding: 5px;
            font-size: 11px;
            color: #666;
            flex-shrink: 0;
        }

        #refresh-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #28a745;
            margin-right: 5px;
            vertical-align: middle;
            animation: blink 3s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .2;
            }
        }

        /* ── BANNER "TAP UNTUK AKTIFKAN SUARA" ── */
        .sound-unlock-hint {
            position: fixed;
            bottom: 14px;
            right: 14px;
            background: #dc3545;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .5);
            z-index: 9999;
            cursor: pointer;
            animation: alertStripBlink 1.4s infinite;
        }
    </style>
</head>

<body>

    <!-- Sesuaikan path file suara ini kalau nama/lokasi file alarm-nya berbeda -->
    <audio id="alarmAudio" src="assets/alarm/standard_emergency_warning.mp3" preload="auto" loop></audio>

    <div class="wrapper">

        <!-- HEADER -->
        <div class="header">
            <a href="index.php" class="back-btn">&#8592; Back</a>
            <div class="header-title">🖥️ MACHINE MONITORING</div>
            <div id="clock">--:--:--</div>
        </div>

        <!-- PLANT COLUMNS -->
        <div class="plants-row">

            <?php
            $plants = [];
            $order = ['assembly', 'test run', 'packing'];
            $q = mysqli_query($conn, "SELECT DISTINCT plant FROM machine");
            $db_plants = [];
            while ($r = mysqli_fetch_assoc($q)) $db_plants[] = $r['plant'];
            // Urutkan sesuai urutan yang diinginkan, sisanya tambahkan di akhir
            foreach ($order as $o) {
                if (in_array($o, $db_plants)) $plants[] = $o;
            }
            foreach ($db_plants as $p) {
                if (!in_array($p, $plants)) $plants[] = $p;
            }

            $col_class = [
                'assembly' => 'col-assembly',
                'test run' => 'col-testrun',
                'packing'  => 'col-packing',
            ];
            $icons = [
                'assembly' => '🔧',
                'test run' => '🧪',
                'packing'  => '📦',
            ];

            foreach ($plants as $plant):
                $slug = preg_replace('/\s+/', '-', strtolower($plant));
                $cls  = $col_class[strtolower($plant)] ?? '';
                $icon = $icons[strtolower($plant)] ?? '🏭';
            ?>
                <div class="plant-col <?= $cls ?>" id="col-<?= $slug ?>">
                    <div class="plant-header">
                        <div class="plant-header-left">
                            <span class="plant-title"><?= $icon ?> <?= htmlspecialchars(ucwords($plant)) ?></span>
                            <button type="button" class="plant-alarm-btn" id="alarm-btn-<?= $slug ?>" title="Alarm mesin stop">
                                <span class="bell-icon">🔔</span>
                                <span class="badge-count-mini">0</span>
                            </button>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                            <span class="plant-summary" id="summary-<?= $slug ?>">...</span>
                            <span class="conveyor-status conv-running" id="conveyor-<?= $slug ?>">
                                <span class="conv-dot"></span> Conveyor ON
                            </span>
                        </div>
                    </div>
                    <div class="plant-alert-strip" id="alert-info-<?= $slug ?>" style="display:none;"></div>
                    <div class="machine_grid" id="grid-<?= $slug ?>">
                        <!-- diisi JS -->
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

    <script>
        // ── CLOCK ──
        function updateClock() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            const timeStr = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('clock').innerHTML =
                `<div class="clock-date">${dateStr}</div>` +
                `<div class="clock-time">${timeStr}</div>`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        const plants = <?= json_encode($plants) ?>;

        // ── STATE ALARM ──
        // key mesin yang di-mute manual: "slug::nama_mesin"
        let mutedMachines = new Set();
        // slug line (assembly/test-run/packing) yang alarm-nya di-mute manual
        let mutedPlants = new Set();
        // daftar SEMUA mesin stopped per line (untuk info bergantian & badge)
        let plantStoppedList = {};
        // daftar mesin stopped yang statusnya "Belum Ditangani" & belum di-mute
        // (inilah yang benar-benar membunyikan alarm)
        let plantPendingAlarmList = {};
        // index rotasi teks info mesin stop per line
        let plantInfoIndex = {};
        let audioUnlocked = false;

        function unlockAlarmAudio() {
            if (audioUnlocked) return;
            const audio = document.getElementById('alarmAudio');
            if (!audio) return;
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audioUnlocked = true;
                const hint = document.getElementById('soundUnlockHint');
                if (hint) hint.style.display = 'none';
            }).catch(() => {});
        }
        document.addEventListener('click', unlockAlarmAudio, {
            once: true
        });

        // Klik tombol alarm di samping nama line → mute/unmute alarm 1 line sekaligus
        plants.forEach(p => {
            const slug = p.replace(/\s+/g, '-').toLowerCase();
            const btn = document.getElementById('alarm-btn-' + slug);
            if (!btn) return;
            btn.addEventListener('click', function() {
                unlockAlarmAudio();
                if (mutedPlants.has(slug)) mutedPlants.delete(slug);
                else mutedPlants.add(slug);
                updatePlantAlarmUI(slug);
            });
        });

        // Update tampilan tombol alarm line + badge + trigger suara global
        function updatePlantAlarmUI(slug) {
            const btn = document.getElementById('alarm-btn-' + slug);
            const infoEl = document.getElementById('alert-info-' + slug);
            if (!btn) return;

            const pending = plantPendingAlarmList[slug] || [];
            const stopped = plantStoppedList[slug] || [];
            const isMuted = mutedPlants.has(slug);
            const hasAlarm = pending.length > 0;

            btn.classList.remove('alarm-active', 'alarm-muted');
            const icon = btn.querySelector('.bell-icon');
            const badge = btn.querySelector('.badge-count-mini');

            if (hasAlarm && !isMuted) {
                btn.classList.add('alarm-active');
                if (icon) icon.textContent = '🔔';
                btn.title = 'Ada mesin stop belum ditangani - klik untuk mute alarm line ini';
            } else if (hasAlarm && isMuted) {
                btn.classList.add('alarm-muted');
                if (icon) icon.textContent = '🔕';
                btn.title = 'Alarm line ini dibisukan - klik untuk aktifkan lagi';
            } else {
                if (icon) icon.textContent = '🔔';
                btn.title = 'Alarm mesin stop';
                // otomatis reset mute begitu sudah tidak ada alarm aktif di line ini
                mutedPlants.delete(slug);
            }

            if (badge) {
                if (stopped.length > 0) {
                    badge.style.display = 'flex';
                    badge.textContent = stopped.length;
                } else {
                    badge.style.display = 'none';
                }
            }

            if (infoEl) {
                if (stopped.length === 0) {
                    infoEl.style.display = 'none';
                    infoEl.textContent = '';
                    plantInfoIndex[slug] = 0;
                } else {
                    infoEl.style.display = 'block';
                }
            }

            updateGlobalAlarm();
        }

        // Nyala/matikan suara alarm berdasarkan semua line yang belum di-mute
        function updateGlobalAlarm() {
            const audio = document.getElementById('alarmAudio');
            if (!audio) return;

            let shouldPlay = false;
            plants.forEach(p => {
                const slug = p.replace(/\s+/g, '-').toLowerCase();
                if (mutedPlants.has(slug)) return;
                const pending = plantPendingAlarmList[slug] || [];
                if (pending.length > 0) shouldPlay = true;
            });

            if (shouldPlay) {
                if (audio.paused) audio.play().catch(() => {});
            } else if (!audio.paused) {
                audio.pause();
            }
        }

        // Info nama mesin stop di bawah judul line, bergantian kalau lebih dari 1
        function tickStoppedInfo() {
            plants.forEach(p => {
                const slug = p.replace(/\s+/g, '-').toLowerCase();
                const infoEl = document.getElementById('alert-info-' + slug);
                const colEl = document.getElementById('col-' + slug);
                if (!infoEl) return;

                const list = plantStoppedList[slug] || [];
                if (list.length === 0) {
                    infoEl.style.display = 'none';
                    infoEl.textContent = '';
                    if (colEl) colEl.classList.remove('has-alert');
                    return;
                }

                infoEl.style.display = 'block';
                if (colEl) colEl.classList.add('has-alert');
                if (!(slug in plantInfoIndex) || plantInfoIndex[slug] >= list.length) {
                    plantInfoIndex[slug] = 0;
                }
                infoEl.textContent = '⚠ ' + list[plantInfoIndex[slug]] + ' - STOPPED';
                plantInfoIndex[slug] = (plantInfoIndex[slug] + 1) % list.length;
            });
        }

        // ── LOAD & RENDER PER PLANT ──
        function loadPlant(plant) {
            const slug = plant.replace(/\s+/g, '-').toLowerCase();
            const grid = document.getElementById('grid-' + slug);
            const sumEl = document.getElementById('summary-' + slug);

            fetch('get_machine_data2.php?plant=' + encodeURIComponent(plant))
                .then(r => r.text())
                .then(html => {
                    // Parse HTML dari server lalu rebuild kartu dengan class yang benar
                    const tmp = document.createElement('div');
                    tmp.innerHTML = html;

                    const cards = tmp.querySelectorAll('.machine_card');
                    let running = 0,
                        abnormal = 0;

                    // dikumpulkan ulang tiap refresh, dipakai untuk alarm line
                    const stoppedNames = [];
                    const pendingAlarmNames = [];

                    cards.forEach(card => {
                        // Wrap machine name lebih dulu supaya nama-nya bisa dipakai
                        // untuk info alarm di bawah
                        // (pakai regex, bukan attribute selector persis, supaya tidak
                        // gagal kalau PHP menulis "font-size: 20px" dengan spasi)
                        const nameDiv = Array.from(card.querySelectorAll('div')).find(
                            d => /font-size:\s*20px/.test(d.getAttribute('style') || '')
                        );
                        let nameText = '';
                        if (nameDiv) {
                            nameText = nameDiv.textContent.trim();
                            nameDiv.className = 'machine-name';
                            nameDiv.removeAttribute('style');
                        }

                        // Baca status dari inline style warna yang diset get_machine_data2.php
                        const statusDiv = card.querySelector('div[style*="color:"]');
                        let isAbnormal = false;
                        if (statusDiv) {
                            const txt = statusDiv.textContent.trim().toUpperCase();
                            if (txt === 'RUNNING') {
                                card.classList.add('status-running');
                                statusDiv.outerHTML =
                                    "<div class='status-badge running'>RUNNING</div>";
                                running++;
                            } else {
                                card.classList.add('status-abnormal');
                                statusDiv.outerHTML =
                                    "<div class='status-badge abnormal'>STOPPED</div>";
                                abnormal++;
                                isAbnormal = true;
                            }
                        }

                        // Reclassify maintenance_status
                        const maint = card.querySelector('.maintenance_status');
                        let maintText = '';
                        if (maint) {
                            maintText = maint.textContent.trim();
                            if (maintText === 'Clear') maint.classList.add('maint-clear');
                            else if (maintText === 'Belum Ditangani') maint.classList.add('maint-pending');
                            else if (maintText === 'Sedang Ditangani') maint.classList.add('maint-progress');
                            maint.removeAttribute('style');
                        }

                        card.removeAttribute('style');

                        // ── ALARM PER MESIN ──
                        const machineKey = slug + '::' + nameText;

                        if (isAbnormal) {
                            stoppedNames.push(nameText);

                            // Alarm cuma "hidup" selama status maintenance masih
                            // "Belum Ditangani". Begitu masuk "Sedang Ditangani"
                            // (progress) atau mesin sudah running lagi, alarm
                            // otomatis berhenti & mute-nya di-reset.
                            const isPending = (maintText === 'Belum Ditangani');
                            if (!isPending) mutedMachines.delete(machineKey);

                            const isMuted = mutedMachines.has(machineKey);
                            const alarmActive = isPending && !isMuted;
                            if (alarmActive) pendingAlarmNames.push(nameText);

                            const bellBtn = document.createElement('button');
                            bellBtn.type = 'button';
                            bellBtn.className = 'machine-alarm-btn' +
                                (alarmActive ? ' alarm-active' :
                                    (isPending && isMuted ? ' alarm-muted' : ' alarm-idle'));
                            bellBtn.innerHTML = (isPending && isMuted) ? '🔕' : '🔔';
                            bellBtn.title = isPending ?
                                (isMuted ?
                                    'Alarm mesin ini dibisukan - klik untuk aktifkan lagi' :
                                    'Klik untuk membisukan alarm mesin ini') :
                                'Sedang ditangani - alarm berhenti otomatis';
                            // Catatan: card di sini masih di dalam DOM sementara (tmp),
                            // jadi listener ditempel via delegasi memakai data-attribute,
                            // bukan addEventListener langsung (hilang saat grid.innerHTML
                            // di-set ulang dari HTML string di bawah).
                            bellBtn.dataset.machineKey = machineKey;

                            // Lonceng dimasukkan ke baris nama (.machine-head),
                            // bukan ditumpuk absolute di pojok kartu, supaya
                            // tidak pernah menindih badge STOPPED / timer.
                            if (nameDiv && nameDiv.parentNode) {
                                const head = document.createElement('div');
                                head.className = 'machine-head';
                                nameDiv.parentNode.insertBefore(head, nameDiv);
                                head.appendChild(nameDiv);
                                head.appendChild(bellBtn);
                            } else {
                                card.appendChild(bellBtn);
                            }
                        } else {
                            // mesin sudah running lagi → reset mute-nya
                            mutedMachines.delete(machineKey);
                        }
                    });

                    grid.innerHTML = tmp.querySelector('.machine_grid')?.innerHTML || html;

                    // Pasang ulang klik listener tombol alarm per mesin
                    // (harus setelah grid.innerHTML di-set, karena innerHTML
                    // membuat elemen baru dan menghapus listener lama)
                    grid.querySelectorAll('.machine-alarm-btn').forEach(btn => {
                        const key = btn.dataset.machineKey;
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            unlockAlarmAudio();
                            if (mutedMachines.has(key)) mutedMachines.delete(key);
                            else mutedMachines.add(key);
                            loadPlant(plant);
                        });
                    });

                    // Langsung tick agar timer tidak flash 00:00:00 saat refresh
                    tickStopwatches();

                    const total = running + abnormal;
                    sumEl.textContent = total + ' mesin · ✅' + running + ' · ❌' + abnormal;

                    plantStoppedList[slug] = stoppedNames;
                    plantPendingAlarmList[slug] = pendingAlarmNames;
                    updatePlantAlarmUI(slug);
                })
                .catch(() => {
                    grid.innerHTML = '<div style="color:#f87171;padding:10px;font-size:12px;">Gagal memuat data</div>';
                });
        }

        function loadAll() {
            plants.forEach(p => loadPlant(p));
            plants.forEach(p => loadConveyor(p));
        }

        // ── LOAD STATUS CONVEYOR PER PLANT ──
        function loadConveyor(plant) {
            const slug = plant.replace(/\s+/g, '-').toLowerCase();
            const el = document.getElementById('conveyor-' + slug);
            if (!el) return;

            fetch('get_conveyor_status.php?plant=' + encodeURIComponent(plant))
                .then(r => r.json())
                .then(data => {
                    const running = parseInt(data.status) === 1;
                    el.className = 'conveyor-status ' + (running ? 'conv-running' : 'conv-stopped');
                    el.innerHTML = `<span class="conv-dot"></span> Conveyor ${running ? 'ON' : 'OFF'}`;
                });
        }

        // ── STOPWATCH: update semua timer abnormal setiap detik ──
        function tickStopwatches() {
            const nowSec = Math.floor(Date.now() / 1000);
            document.querySelectorAll('.abnormal_time[data-since]').forEach(el => {
                // data-since  = elapsed detik saat server render
                // data-render = waktu client (epoch detik) saat elemen pertama masuk DOM
                if (!el.dataset.render) {
                    el.dataset.render = nowSec;
                }
                const elapsedAtRender = parseInt(el.dataset.since);
                const secondsSinceRender = nowSec - parseInt(el.dataset.render);
                const total = elapsedAtRender + secondsSinceRender;

                if (total < 0) return;

                const h = Math.floor(total / 3600);
                const m = Math.floor((total % 3600) / 60);
                const s = total % 60;
                el.textContent =
                    String(h).padStart(2, '0') + ':' +
                    String(m).padStart(2, '0') + ':' +
                    String(s).padStart(2, '0');
            });
        }

        loadAll();
        setInterval(loadAll, 3000);
        setInterval(tickStopwatches, 1000);
        setInterval(tickStoppedInfo, 2000);
    </script>

</body>

</html>