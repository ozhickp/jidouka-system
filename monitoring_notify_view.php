<?php
// Tampilan bersama untuk monitoring_picking_hde.php dan monitoring_maintenance.php.
// Variabel $TARGET ('picking_yard' | 'maintenance') dan $PAGE_TITLE di-set oleh file pemanggil.
include "config.php";
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($PAGE_TITLE) ?></title>
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
            color: #fff;
            letter-spacing: .5px;
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
            background: var(--line-color, #444);
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

        .col-assembly {
            --line-color: #4400A8;
        }

        .col-testrun {
            --line-color: #00717F;
        }

        .col-packing {
            --line-color: #AB03A9;
        }

        /* Strip info proses stop di bawah nama line */
        .plant-alert-strip {
            background: #4a1a1a;
            color: #ff8a8a;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            padding: 4px 10px;
            letter-spacing: .3px;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        /* ── GRID KARTU ── */
        .machine_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            grid-auto-rows: minmax(0, 1fr);
            gap: 6px;
            padding: 8px;
            overflow: hidden;
            align-content: stretch;
            flex: 1;
            min-height: 0;
        }

        .col-assembly .machine_grid {
            grid-template-columns: repeat(3, 1fr);
        }

        /* ── KARTU: hanya 2 kondisi, hijau & merah ── */
        .machine_card {
            background: #1E8E3E;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 6px 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            min-height: 0;
        }

        .machine_card.status-stopped {
            background: #D50000;
            border-color: #ff8a94;
        }

        .machine-name {
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 4px;
            line-height: 1.2;
            min-height: 2.4em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex-shrink: 0;
        }

        .col-assembly .machine_card {
            padding: 4px 6px;
        }

        .col-assembly .machine-name {
            font-size: 10.5px;
            line-height: 1.15;
            margin-bottom: 3px;
            min-height: 2.3em;
        }

        .status-badge {
            display: inline-block;
            align-self: center;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: #fff;
            flex-shrink: 0;
        }

        .status-badge.running {
            background: rgba(0, 0, 0, .35);
        }

        .status-badge.stopped {
            background: #7a0f1c;
        }

        .col-assembly .status-badge {
            font-size: 10px;
            padding: 1px 6px;
        }

        .abnormal_time {
            margin-top: 3px;
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            background: rgba(0, 0, 0, .25);
            border-radius: 4px;
            padding: 2px 6px;
            letter-spacing: 1px;
            font-family: monospace;
            flex-shrink: 0;
        }

        .col-assembly .abnormal_time {
            font-size: 11px;
            padding: 1px 5px;
            margin-top: 2px;
        }

        /* ── CARD MERAH: nama → pesan (inti) → footer status + waktu ──
           Ukuran huruf pesan & footer dihitung otomatis oleh fitCards() (JS)
           supaya selalu pas dengan ukuran card. */
        .machine_card.status-stopped {
            justify-content: space-between;
            padding: 4px 6px;
        }

        .machine_card.status-stopped .machine-name {
            min-height: 0;
            font-size: 11px;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .col-assembly .machine_card.status-stopped .machine-name {
            font-size: 10.5px;
        }

        .notify-msg {
            flex: 1 1 0;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 2px 0;
            padding: 2px 4px;
            border-top: 1px solid rgba(255, 255, 255, .4);
            border-bottom: 1px solid rgba(255, 255, 255, .4);
            overflow: hidden;
        }

        .notify-msg .nm-text {
            color: #fff;
            font-weight: 800;
            line-height: 1.1;
            max-width: 100%;
            word-break: break-word;
            overflow-wrap: anywhere;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .35);
        }

        .stop-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5em;
            background: rgba(0, 0, 0, .35);
            border-radius: 6px;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
        }

        .stop-foot .sf-label {
            font-size: .85em;
            letter-spacing: .04em;
            flex-shrink: 0;
        }

        .stop-foot .sf-sep {
            opacity: .45;
            flex-shrink: 0;
        }

        .machine_card .stop-foot .abnormal_time {
            background: none;
            margin: 0;
            padding: 0;
            font-size: 1em;
            letter-spacing: 0;
            flex-shrink: 0;
        }

        /* ── CONVEYOR ── */
        .conveyor-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
            color: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .3);
        }

        .conveyor-status.conv-running {
            background: #28a745;
        }

        .conveyor-status.conv-stopped {
            background: #dc3545;
        }

        .conv-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fff;
            flex-shrink: 0;
        }

        .conv-stopped .conv-dot {
            animation: blink 1s infinite;
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

        <div class="header">
            <a href="index.php" class="back-btn">&#8592; Back</a>
            <div class="header-title"><?= htmlspecialchars($PAGE_TITLE) ?></div>
            <div id="clock">--:--:--</div>
        </div>

        <div class="plants-row">
            <?php
            $plants = [];
            $order = ['assembly', 'test run', 'packing'];
            $q = mysqli_query($conn, "SELECT DISTINCT plant FROM machine");
            $db_plants = [];
            while ($r = mysqli_fetch_assoc($q)) $db_plants[] = $r['plant'];
            foreach ($order as $o) {
                if (in_array($o, $db_plants)) $plants[] = $o;
            }
            foreach ($db_plants as $p) {
                if (!in_array($p, $plants)) $plants[] = $p;
            }

            $col_class = ['assembly' => 'col-assembly', 'test run' => 'col-testrun', 'packing' => 'col-packing'];
            $icons     = ['assembly' => '🔧', 'test run' => '🧪', 'packing' => '📦'];

            foreach ($plants as $plant):
                $slug = preg_replace('/\s+/', '-', strtolower($plant));
                $cls  = $col_class[strtolower($plant)] ?? '';
                $icon = $icons[strtolower($plant)] ?? '🏭';
            ?>
                <div class="plant-col <?= $cls ?>" id="col-<?= $slug ?>">
                    <div class="plant-header">
                        <span><?= $icon ?> <?= htmlspecialchars(ucwords($plant)) ?></span>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                            <span class="plant-summary" id="summary-<?= $slug ?>">...</span>
                            <span class="conveyor-status conv-running" id="conveyor-<?= $slug ?>">
                                <span class="conv-dot"></span> Conveyor ON
                            </span>
                        </div>
                    </div>
                    <div class="plant-alert-strip" id="alert-info-<?= $slug ?>" style="display:none;"></div>
                    <div class="machine_grid" id="grid-<?= $slug ?>"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const TARGET = <?= json_encode($TARGET) ?>;
        const plants = <?= json_encode($plants) ?>;

        const slugOf = p => p.replace(/\s+/g, '-').toLowerCase();
        const keyOf = (plant, name) => plant.toLowerCase().trim() + '::' + name.toLowerCase().trim();

        // pesan aktif dari get_notify.php: key "plant::nama proses" -> data pesan
        let notifyMap = {};
        let plantStoppedList = {};
        let plantInfoIndex = {};

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
                `<div class="clock-date">${dateStr}</div><div class="clock-time">${timeStr}</div>`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ── Kosongkan pesan di server kalau proses sudah RUNNING lagi ──
        const clearing = new Set();

        function clearNotify(plant, name) {
            const k = keyOf(plant, name);
            if (clearing.has(k)) return;
            clearing.add(k);
            fetch('clear_notify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `target=${encodeURIComponent(TARGET)}&plant=${encodeURIComponent(plant)}&machine_name=${encodeURIComponent(name)}`
                })
                .catch(() => {})
                .finally(() => {
                    delete notifyMap[k];
                    clearing.delete(k);
                });
        }

        // ── Sesuaikan ukuran huruf pesan & footer dengan ukuran card ──
        function fitCards(grid) {
            const isAsm = !!grid.closest('.col-assembly');

            grid.querySelectorAll('.notify-msg').forEach(box => {
                const txt = box.querySelector('.nm-text');
                if (!txt) return;
                const cs = getComputedStyle(box);
                const availH = box.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
                const availW = box.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
                let size = isAsm ? 18 : 24;
                txt.style.fontSize = size + 'px';
                while (size > 8 && (txt.offsetHeight > availH || txt.scrollWidth > availW + 1)) {
                    size -= 0.5;
                    txt.style.fontSize = size + 'px';
                }
            });

            grid.querySelectorAll('.stop-foot').forEach(f => {
                let size = isAsm ? 13 : 15;
                f.style.fontSize = size + 'px';
                while (size > 7 && f.scrollWidth > f.clientWidth + 1) {
                    size -= 0.5;
                    f.style.fontSize = size + 'px';
                }
            });
        }

        // ── LOAD & RENDER PER PLANT ──
        function loadPlant(plant) {
            const slug = slugOf(plant);
            const grid = document.getElementById('grid-' + slug);
            const sumEl = document.getElementById('summary-' + slug);

            return fetch('get_machine_data2.php?plant=' + encodeURIComponent(plant))
                .then(r => r.text())
                .then(html => {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = html;

                    // Baca nama, status ASLI, dan lama stop dari HTML get_machine_data2.php
                    // (cara yang sama dengan monitoring_public.php)
                    const items = [];
                    tmp.querySelectorAll('.machine_card').forEach(card => {
                        const nameDiv = Array.from(card.querySelectorAll('div')).find(
                            d => /font-size:\s*20px/.test(d.getAttribute('style') || ''));
                        const name = nameDiv ? nameDiv.textContent.trim() : '';
                        const statusDiv = Array.from(card.querySelectorAll('div[style*="color:"]')).find(
                            d => !/font-size:\s*20px/.test(d.getAttribute('style') || ''));
                        const stopped = statusDiv ? statusDiv.textContent.trim().toUpperCase() !== 'RUNNING' : false;
                        const timer = card.querySelector('.abnormal_time[data-since]');
                        const since = timer ? (parseInt(timer.dataset.since) || 0) : 0;
                        items.push({
                            name,
                            stopped,
                            since
                        });
                    });

                    grid.innerHTML = '';
                    let green = 0,
                        red = 0;
                    const redNames = [];
                    const now = Date.now();

                    items.forEach(it => {
                        const k = keyOf(plant, it.name);
                        const n = notifyMap[k];

                        // proses sudah normal lagi → pesan lama dibersihkan
                        if (n && !it.stopped) clearNotify(plant, it.name);

                        // MERAH hanya kalau ada pesan aktif DAN proses memang masih stop
                        const isRed = !!n && it.stopped;

                        const card = document.createElement('div');
                        card.className = 'machine_card' + (isRed ? ' status-stopped' : '');

                        const nameEl = document.createElement('div');
                        nameEl.className = 'machine-name';
                        nameEl.textContent = it.name;
                        card.appendChild(nameEl);

                        if (isRed) {
                            red++;
                            redNames.push({
                                name: it.name,
                                message: n.message
                            });

                            // Pesan = inti card (nama pengirim tidak ditampilkan)
                            const m = document.createElement('div');
                            m.className = 'notify-msg';
                            m.title = n.message;
                            const txt = document.createElement('div');
                            txt.className = 'nm-text';
                            txt.textContent = n.message;
                            m.appendChild(txt);
                            card.appendChild(m);

                            // Footer: STOPPED | stopwatch
                            const foot = document.createElement('div');
                            foot.className = 'stop-foot';
                            const lbl = document.createElement('span');
                            lbl.className = 'sf-label';
                            lbl.textContent = 'STOPPED';
                            const sep = document.createElement('span');
                            sep.className = 'sf-sep';
                            sep.textContent = '|';
                            const t = document.createElement('span');
                            t.className = 'abnormal_time';
                            t.dataset.since = it.since;
                            t.dataset.t0 = now;
                            foot.appendChild(lbl);
                            foot.appendChild(sep);
                            foot.appendChild(t);
                            card.appendChild(foot);
                        } else {
                            const badge = document.createElement('div');
                            badge.className = 'status-badge running';
                            badge.textContent = 'RUNNING';
                            card.appendChild(badge);
                            green++;
                        }
                        grid.appendChild(card);
                    });

                    tickStopwatches();
                    fitCards(grid);
                    sumEl.textContent = (green + red) + ' proses · ✅' + green + ' · ❌' + red;
                    plantStoppedList[slug] = redNames;
                    if (redNames.length === 0) {
                        const col = document.getElementById('col-' + slug);
                        if (col) col.classList.remove('has-alert');
                        const infoEl = document.getElementById('alert-info-' + slug);
                        if (infoEl) infoEl.style.display = 'none';
                    }
                })
                .catch(() => {
                    grid.innerHTML = '<div style="color:#f87171;padding:10px;font-size:12px;">Gagal memuat data</div>';
                });
        }

        // ── Info proses stop bergantian di bawah nama line ──
        function tickStoppedInfo() {
            plants.forEach(p => {
                const slug = slugOf(p);
                const infoEl = document.getElementById('alert-info-' + slug);
                if (!infoEl) return;
                const list = plantStoppedList[slug] || [];
                if (list.length === 0) {
                    infoEl.style.display = 'none';
                    plantInfoIndex[slug] = 0;
                    return;
                }
                infoEl.style.display = 'block';
                if (!(slug in plantInfoIndex) || plantInfoIndex[slug] >= list.length) plantInfoIndex[slug] = 0;
                const it = list[plantInfoIndex[slug]];
                infoEl.textContent = '⚠ ' + it.name + (it.message ? ' - ' + it.message : '');
                plantInfoIndex[slug] = (plantInfoIndex[slug] + 1) % list.length;
            });
        }

        // ── CONVEYOR ──
        function loadConveyor(plant) {
            const el = document.getElementById('conveyor-' + slugOf(plant));
            if (!el) return;
            fetch('get_conveyor_status.php?plant=' + encodeURIComponent(plant))
                .then(r => r.json())
                .then(data => {
                    const running = parseInt(data.status) === 1;
                    el.className = 'conveyor-status ' + (running ? 'conv-running' : 'conv-stopped');
                    el.innerHTML = `<span class="conv-dot"></span> Conveyor ${running ? 'ON' : 'OFF'}`;
                })
                .catch(() => {});
        }

        // ── STOPWATCH ──
        function tickStopwatches() {
            const now = Date.now();
            document.querySelectorAll('.abnormal_time[data-since]').forEach(el => {
                const total = parseInt(el.dataset.since) + Math.floor((now - parseInt(el.dataset.t0)) / 1000);
                if (total < 0) return;
                const h = Math.floor(total / 3600);
                const m = Math.floor((total % 3600) / 60);
                const s = total % 60;
                el.textContent = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            });
        }

        // ── REFRESH ──
        async function loadAll() {
            try {
                const r = await fetch('get_notify.php?target=' + encodeURIComponent(TARGET));
                const list = await r.json();
                const map = {};
                list.forEach(n => {
                    map[keyOf(n.plant, n.machine_name)] = n;
                });
                notifyMap = map;
            } catch (e) {
                /* pakai data terakhir kalau gagal */
            }
            plants.forEach(p => {
                loadPlant(p);
                loadConveyor(p);
            });
        }

        loadAll();
        setInterval(loadAll, 3000);
        setInterval(tickStopwatches, 1000);
        setInterval(tickStoppedInfo, 2000);
    </script>
</body>

</html>