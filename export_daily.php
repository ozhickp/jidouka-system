<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

session_start();
include 'config.php';

if (!defined('EXPORT_AUTOLOAD')) {
    define('EXPORT_AUTOLOAD', __DIR__ . '/vendor/autoload.php');
}
if (!is_file(EXPORT_AUTOLOAD)) {
    http_response_code(500);
    die('Autoloader PhpSpreadsheet tidak ditemukan: ' . EXPORT_AUTOLOAD);
}
require EXPORT_AUTOLOAD;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ── Parameter ─────────────────────────────────────────────────────────────────
$tanggal = trim($_GET['tanggal'] ?? '');
if ($tanggal === '') die('Pilih tanggal terlebih dahulu.');
$dt = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$dt || $dt->format('Y-m-d') !== $tanggal) die('Format tanggal tidak valid.');

// ── Query ─────────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("
SELECT 
    m.machine_name, m.plant, e.engine_name,
    pcm.name AS category_machine_name,
    pce.name AS category_engine_name,
    l.*,
    dl.downtime_start, dl.downtime_end
FROM maintenance_logs l
JOIN machine m ON l.machine_id = m.id
LEFT JOIN engine e ON l.engine_id = e.id
LEFT JOIN problem_category pcm ON l.category_machine_id = pcm.id
LEFT JOIN problem_category pce ON l.category_engine_id = pce.id
LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = l.id
WHERE DATE(l.waktu_mulai) = ?
ORDER BY l.waktu_mulai ASC
");
$stmt->bind_param('s', $tanggal);
$stmt->execute();
$result = $stmt->get_result();

/* EXCEL */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Daily Report");

/* SET WIDTH KHUSUS GAMBAR */
$sheet->getColumnDimension('L')->setWidth(25);
$sheet->getColumnDimension('M')->setWidth(25);

/* LOGO */
if (file_exists('assets/company_logo.jpg')) {
    $logo = new Drawing();
    $logo->setName('Company Logo');
    $logo->setPath('assets/company_logo.jpg');
    $logo->setHeight(60);
    $logo->setCoordinates('A1');
    $logo->setWorksheet($sheet);
}

/* TITLE */
$sheet->mergeCells('A1:T1');
$sheet->setCellValue('A1', 'HISTORY MAINTENANCE REPORT');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:T2');
$sheet->setCellValue('A2', 'Tanggal : ' . $tanggal);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

/* HEADER */
$header = [
    'No',
    'Machine',
    'Plant',
    'Machine Category',
    'Machine Problem',
    'Machine Action',
    'Engine Type',
    'Serial Number',
    'Engine Category',
    'Engine Problem',
    'Engine Action',
    'Documentation Machine',
    'Documentation Engine',
    'Handled By',
    'Downtime Start',
    'Downtime End',
    'Maintenance Start',
    'Maintenance End',
    'Downtime Duration (sec)',
    'Maintenance Duration (sec)'
];

$sheet->fromArray($header, NULL, 'A4');

$sheet->getStyle('A4:T4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

$row = 5;
$no = 1;
$totalDowntime = 0;
$totalMaintenance = 0;

while ($data = $result->fetch_assoc()) {

    $sheet->getRowDimension($row)->setRowHeight(80);

    // Durasi dalam detik (angka murni, tanpa satuan, supaya bisa dijumlah)
    $downtimeDuration = '-';
    if ($data['downtime_start'] && $data['downtime_end']) {
        $downtimeDuration = max(0, strtotime($data['downtime_end']) - strtotime($data['downtime_start']));
        $totalDowntime += $downtimeDuration;
    }

    $maintDuration = '-';
    if ($data['waktu_mulai'] && $data['waktu_selesai']) {
        $maintDuration = max(0, strtotime($data['waktu_selesai']) - strtotime($data['waktu_mulai']));
        $totalMaintenance += $maintDuration;
    }

    $sheet->fromArray([
        $no++,
        $data['machine_name'],
        $data['plant'],
        $data['category_machine_name'] ?: '-',
        $data['kerusakan_machine'] ?: '-',
        $data['perbaikan_machine'] ?: '-',
        $data['engine_name'] ?: '-',
        $data['engine_serial'] ?: '-',
        $data['category_engine_name'] ?: '-',
        $data['kerusakan_engine'] ?: '-',
        $data['perbaikan_engine'] ?: '-',
        '',
        '',
        $data['handled_by'],
        $data['downtime_start'],
        $data['downtime_end'],
        $data['waktu_mulai'],
        $data['waktu_selesai'],
        $downtimeDuration,
        $maintDuration
    ], NULL, "A$row");

    $sheet->getStyle("O$row:R$row")->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');

    /* MACHINE IMAGE */
    if (!empty($data['dokumentasi_machine'])) {
        $img = 'uploads/machine/' . $data['dokumentasi_machine'];
        if (file_exists($img)) {
            $drawing = new Drawing();
            $drawing->setPath($img);
            $drawing->setHeight(70);
            $drawing->setCoordinates("L$row");
            $drawing->setOffsetX(15);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }
    }

    /* ENGINE IMAGE */
    if (!empty($data['dokumentasi_engine'])) {
        $img = 'uploads/engine/' . $data['dokumentasi_engine'];
        if (file_exists($img)) {
            $drawing = new Drawing();
            $drawing->setPath($img);
            $drawing->setHeight(70);
            $drawing->setCoordinates("M$row");
            $drawing->setOffsetX(15);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }
    }

    $row++;
}

/* TOTAL ROW */
$totalRow = $row;
$sheet->mergeCells("A$totalRow:R$totalRow");
$sheet->setCellValue("A$totalRow", 'TOTAL');
$sheet->setCellValue("S$totalRow", $totalDowntime);
$sheet->setCellValue("T$totalRow", $totalMaintenance);
$sheet->getStyle("S5:T$totalRow")->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle("A$totalRow:T$totalRow")->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
]);

/* AUTO SIZE */
foreach (range('A', 'T') as $col) {
    if (!in_array($col, ['L', 'M']))
        $sheet->getColumnDimension($col)->setAutoSize(true);
}

$sheet->getStyle("A4:T" . $totalRow)->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]
]);

$sheet->getStyle("A4:T" . $totalRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

$sheet->freezePane('A5');

// ── Export ────────────────────────────────────────────────────────────────────
// Sama seperti export_history_report.php [FIX-3]: simpan ke file temp dulu, baru
// kirim ke browser. Langsung ke php://output berisiko: koneksi bisa dianggap
// menggantung / file terpotong kalau proses generate (apalagi dengan banyak
// gambar) belum selesai saat data mulai dikirim.
$filename = "History_Maintenance_$tanggal.xlsx";

$writer = new Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);

$tmpFile = tempnam(sys_get_temp_dir(), 'maint_daily_');
try {
    $writer->save($tmpFile);
} catch (Throwable $e) {
    @unlink($tmpFile);
    http_response_code(500);
    die('Gagal membuat file Excel: ' . $e->getMessage());
}

// Buang output yang mungkin sudah terlanjur keluar (spasi/BOM dari include),
// supaya isi file .xlsx tidak rusak.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tmpFile));

readfile($tmpFile);
unlink($tmpFile);
exit;