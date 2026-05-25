<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$path = $argv[1] ?? null;
$maxRows = (int) ($argv[2] ?? 40);
if (! $path) {
    fwrite(STDERR, "Usage: php scripts/dump_xlsx.php <path.xlsx> [maxRows]\n");
    exit(2);
}

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
$spreadsheet = $reader->load($path);
$sheet = $spreadsheet->getSheet(0);

$highestRow = $sheet->getHighestDataRow();
$highestCol = $sheet->getHighestDataColumn();
$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

echo "sheetTitle=".$sheet->getTitle().PHP_EOL;
echo "highestRow=".$highestRow." highestCol=".$highestCol." (".$highestColIndex.")".PHP_EOL;

$limit = min($maxRows, $highestRow);
for ($r = 1; $r <= $limit; $r++) {
    $vals = [];
    for ($c = 1; $c <= $highestColIndex; $c++) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $vals[] = $sheet->getCell($col.$r)->getFormattedValue();
    }
    echo $r.": ".json_encode($vals, JSON_UNESCAPED_UNICODE).PHP_EOL;
}

