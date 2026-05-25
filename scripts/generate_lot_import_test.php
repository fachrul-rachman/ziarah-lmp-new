<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__.'/../vendor/autoload.php';

$outPath = $argv[1] ?? (__DIR__.'/../storage/app/imports/lot-import-test.xlsx');
$rows = (int) ($argv[2] ?? 1100);

$dir = dirname($outPath);
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray(['jenis_makam', 'lokasi', 'zona', 'no_lot', 'ukuran'], null, 'A1');

for ($i = 0; $i < $rows; $i++) {
    $row = $i + 2;
    $sheet->setCellValue('A'.$row, 'Makam');
    $sheet->setCellValue('B'.$row, 'Lokasi Test');
    $sheet->setCellValue('C'.$row, 'Zona 1');
    $sheet->setCellValue('D'.$row, 'L'.($i + 1));
    $sheet->setCellValue('E'.$row, 'Single');
}

$writer = new Xlsx($spreadsheet);
$writer->save($outPath);

echo "Generated: {$outPath} (rows={$rows})\n";

