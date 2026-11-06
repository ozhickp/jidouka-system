<?php
include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$where="";

if(isset($_GET['tanggal']) && $_GET['tanggal']!=""){
    $tgl=$_GET['tanggal'];
    $where="WHERE DATE(l.waktu_mulai)='$tgl'";
}

if(isset($_GET['bulan']) && $_GET['bulan']!=""){
    $bulan=$_GET['bulan'];
    $where="WHERE DATE_FORMAT(l.waktu_mulai,'%Y-%m')='$bulan'";
}

$query="
SELECT m.machine_name,l.*
FROM maintenance_logs l
JOIN machine m ON l.machine_id=m.id
$where
ORDER BY l.waktu_mulai ASC
";

$result=$conn->query($query);

$spreadsheet=new Spreadsheet();
$sheet=$spreadsheet->getActiveSheet();

$sheet->fromArray([
    ["Machine","Handled By","Kerusakan","Perbaikan","Mulai","Selesai"]
]);

$rowNum=2;

while($row=$result->fetch_assoc()){
    $sheet->fromArray([
        $row['machine_name'],
        $row['handled_by'],
        $row['kerusakan'],
        $row['perbaikan'],
        $row['waktu_mulai'],
        $row['waktu_selesai']
    ],NULL,"A$rowNum");
    $rowNum++;
}

$writer=new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="maintenance_report.xlsx"');
$writer->save("php://output");
exit;