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

        /* ── MACHINE GRID (di dalam kolom) ── */
        .machine_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
            padding: 10px;
            overflow: hidden;
            /* tidak scroll */
            align-content: start;
            flex: 1;
            min-height: 0;
        }

        /* Assembly punya 16 mesin → paksa 4 kolom agar muat */
        .col-assembly .machine_grid {
            grid-template-columns: repeat(4, 1fr);
        }

        /* ── MACHINE CARD ── */
        .machine_card {
            background: #2c3136;
            border-radius: 8px;
            padding: 10px 8px;
            text-align: center;
            border: 2px solid #444;
            transition: border-color .3s;
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
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .status-badge.running {
            background: #28a745;
        }

        .status-badge.abnormal {
            background: #dc3545;
        }

        .maintenance_status {
            margin-top: 5px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 5px;
            font-weight: 600;
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
            margin-top: 4px;
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            background: rgba(0, 0, 0, 0.25);
            border-radius: 4px;
            padding: 3px 6px;
            letter-spacing: 1px;
            font-family: monospace;
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
    </style>
</head>

<body>

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
                        <span><?= $icon ?> <?= htmlspecialchars(ucwords($plant)) ?></span>
                        <span class="plant-summary" id="summary-<?= $slug ?>">...</span>
                    </div>
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

                    cards.forEach(card => {
                        // Baca status dari inline style warna yang diset get_machine_data2.php
                        const statusDiv = card.querySelector('div[style*="color:"]');
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
                            }
                        }

                        // Reclassify maintenance_status
                        const maint = card.querySelector('.maintenance_status');
                        if (maint) {
                            const t = maint.textContent.trim();
                            if (t === 'Clear') maint.classList.add('maint-clear');
                            else if (t === 'Belum Ditangani') maint.classList.add('maint-pending');
                            else if (t === 'Sedang Ditangani') maint.classList.add('maint-progress');
                            maint.removeAttribute('style');
                        }

                        // Wrap machine name
                        const nameDiv = card.querySelector('div[style*="font-size:20px"]');
                        if (nameDiv) {
                            nameDiv.className = 'machine-name';
                            nameDiv.removeAttribute('style');
                        }

                        card.removeAttribute('style');
                    });

                    grid.innerHTML = tmp.querySelector('.machine_grid')?.innerHTML || html;

                    // Langsung tick agar timer tidak flash 00:00:00 saat refresh
                    tickStopwatches();

                    const total = running + abnormal;
                    sumEl.textContent = total + ' mesin · ✅' + running + ' · ❌' + abnormal;
                })
                .catch(() => {
                    grid.innerHTML = '<div style="color:#f87171;padding:10px;font-size:12px;">Gagal memuat data</div>';
                });
        }

        function loadAll() {
            plants.forEach(p => loadPlant(p));
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
    </script>

</body>

</html>