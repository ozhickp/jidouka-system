<?php
session_start();
include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$bulan = $_GET['bulan'] ?? '';
if ($bulan == "") die("Pilih bulan terlebih dahulu.");

/* QUERY DATA */
$query = "
SELECT 
    m.machine_name, m.plant,
    l.*,
    dl.downtime_start, dl.downtime_end
FROM maintenance_logs l
JOIN machine m ON l.machine_id = m.id
LEFT JOIN engine e ON l.engine_id = e.id
LEFT JOIN downtime_logs dl ON dl.maintenance_logs_id = l.id
WHERE DATE_FORMAT(l.waktu_mulai,'%Y-%m') = '$bulan'
ORDER BY l.waktu_mulai ASC
";

$result = $conn->query($query);

/* EXCEL */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Monthly Report");

/* SET WIDTH KOLOM GAMBAR */
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(25);

/* LOGO */
$logo = new Drawing();
$logo->setName('Company Logo');
$logo->setPath('assets/company_logo.jpg');
$logo->setHeight(60);
$logo->setCoordinates('A1');
$logo->setWorksheet($sheet);

/* TITLE */
$sheet->mergeCells('A1:P1');
$sheet->setCellValue('A1', 'HISTORY MAINTENANCE REPORT');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:P2');
$sheet->setCellValue('A2', 'Bulan : ' . $bulan);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

/* HEADER */
$header = [
    'No',
    'Machine',
    'Plant',
    'Machine Problem',
    'Machine Action',
    'Engine Problem',
    'Engine Action',
    'Documentation Machine',
    'Documentation Engine',
    'Handled By',
    'Downtime Start',
    'Downtime End',
    'Maintenance Start',
    'Maintenance End',
    'Downtime Duration',
    'Maintenance Duration'
];

$sheet->fromArray($header, NULL, 'A4');

$sheet->getStyle('A4:P4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

$row = 5;
$no = 1;

while ($data = $result->fetch_assoc()) {

    /* TINGGI ROW AGAR GAMBAR MASUK */
    $sheet->getRowDimension($row)->setRowHeight(80);

    /* HITUNG DURASI */
    $downtimeDuration = '-';
    if ($data['downtime_start'] && $data['downtime_end']) {
        $downtimeDuration = round((strtotime($data['downtime_end']) - strtotime($data['downtime_start'])) / 60) . ' min';
    }

    $maintDuration = '-';
    if ($data['waktu_mulai'] && $data['waktu_selesai']) {
        $maintDuration = round((strtotime($data['waktu_selesai']) - strtotime($data['waktu_mulai'])) / 60) . ' min';
    }

    /* DATA TEXT */
    $sheet->fromArray([
        $no++,
        $data['machine_name'],
        $data['plant'],
        $data['kerusakan_machine'] ?: '-',
        $data['perbaikan_machine'] ?: '-',
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

    /* FORMAT DATETIME */
    $sheet->getStyle("K$row:N$row")->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');

    /* GAMBAR MACHINE */
    if (!empty($data['dokumentasi_machine'])) {

        $imgPath = 'uploads/machine/' . $data['dokumentasi_machine'];

        if (file_exists($imgPath)) {

            $drawing = new Drawing();
            $drawing->setPath($imgPath);
            $drawing->setHeight(70);
            $drawing->setCoordinates("H$row");
            $drawing->setOffsetX(15);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }
    }

    /* GAMBAR ENGINE */
    if (!empty($data['dokumentasi_engine'])) {

        $imgPath = 'uploads/engine/' . $data['dokumentasi_engine'];

        if (file_exists($imgPath)) {

            $drawing = new Drawing();
            $drawing->setPath($imgPath);
            $drawing->setHeight(70);
            $drawing->setCoordinates("I$row");
            $drawing->setOffsetX(15);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }
    }

    $row++;
}

/* AUTO SIZE KOLOM */
foreach (range('A', 'P') as $col) {

    if (!in_array($col, ['H', 'I'])) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

/* ALIGNMENT */
$sheet->getStyle("A4:P" . ($row - 1))->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]
]);

/* BORDER */
$sheet->getStyle("A4:P" . ($row - 1))->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
]);

/* FREEZE HEADER */
$sheet->freezePane('A5');

/* EXPORT */
$writer = new Xlsx($spreadsheet);
$filename = "History_Maintenance_$bulan.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer->save("php://output");
exit;
