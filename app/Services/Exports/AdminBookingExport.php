<?php

namespace App\Services\Exports;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class AdminBookingExport implements FromArray, WithEvents
{
    /**
     * @param array<int,array<string,mixed>> $rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly ?string $minDate,
        private readonly ?string $maxDate,
        private readonly bool $hideActivityColumn = false,
    ) {
    }

    public function array(): array
    {
        $columns = $this->columnCount();

        $header1 = array_pad(['DATA BOOKING'], $columns, '');
        $header2 = array_pad(['TANGGAL EXPORT: '.$this->formatRange()], $columns, '');

        $ziarahRows = array_values(array_filter($this->rows, fn ($r) => ($r['activity_type'] ?? '') === 'ziarah'));
        $kegiatanRows = array_values(array_filter($this->rows, fn ($r) => ($r['activity_type'] ?? '') !== 'ziarah'));

        $out = [];
        $out[] = $header1;
        $out[] = $header2;

        if (count($ziarahRows) > 0) {
            $out = array_merge($out, $this->section('Data Ziarah', $ziarahRows));
        }
        if (count($kegiatanRows) > 0) {
            $out = array_merge($out, $this->section('Data Kegiatan', $kegiatanRows));
        }

        if (count($out) <= 2) {
            $out = array_merge($out, $this->section('Data Ziarah', []));
        }

        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<int,string>>
     */
    private function section(string $title, array $rows): array
    {
        $columns = $this->columnCount();
        $out = [];

        $titleRow = array_fill(0, $columns, '');
        $titleRow[0] = $title;
        $titleRow[$this->facilityStartIndex()] = 'Fasilitas';
        $out[] = $titleRow;

        $out[] = $this->headers();

        $totTent = 0;
        $totChairs = 0;
        $totBurn = 0;
        $totPrayer = 0;
        $totLamp = 0;

        foreach ($rows as $idx => $r) {
            $tenda = (int) ($r['has_tent'] ?? 0);
            $kursi = (int) ($r['chairs_count'] ?? 0);
            $tong = (int) ($r['burn_barrels_count'] ?? 0);
            $meja = (bool) ($r['has_prayer_table'] ?? false);
            $lampu = (bool) ($r['has_lamp'] ?? false);

            $totTent += $tenda;
            $totChairs += $kursi;
            $totBurn += $tong;
            $totPrayer += $meja ? 1 : 0;
            $totLamp += $lampu ? 1 : 0;

            $row = [];
            $row[] = (string) ($idx + 1);

            if (! $this->hideActivityColumn) {
                $row[] = (string) ($r['activity_label'] ?? '');
            }

            $row[] = (string) ($r['time_range'] ?? '');
            $row[] = (string) ($r['location'] ?? '');
            $row[] = (string) ($r['customer_name'] ?? '');
            $row[] = (string) ($r['grave_label'] ?? '');
            $row[] = (string) ($r['zone'] ?? '');
            $row[] = (string) ($r['lot'] ?? '');
            $row[] = (string) $tenda;
            $row[] = (string) $kursi;
            $row[] = (string) $tong;
            $row[] = $meja ? 'Ya' : 'Tidak';
            $row[] = $lampu ? 'Ya' : 'Tidak';

            $out[] = $row;
        }

        $padCount = max(0, 9 - count($rows));
        for ($i = 0; $i < $padCount; $i++) {
            $out[] = array_fill(0, $columns, '');
        }

        $totalRow = array_fill(0, $columns, '');
        $totalRow[$this->totalLabelIndex()] = 'Total Kebutuhan Fasilitas:';
        $totalRow[$this->facilityStartIndex() + 0] = (string) $totTent;
        $totalRow[$this->facilityStartIndex() + 1] = (string) $totChairs;
        $totalRow[$this->facilityStartIndex() + 2] = (string) $totBurn;
        $totalRow[$this->facilityStartIndex() + 3] = (string) $totPrayer;
        $totalRow[$this->facilityStartIndex() + 4] = (string) $totLamp;
        $out[] = $totalRow;

        return $out;
    }

    /**
     * @return array<int,string>
     */
    private function headers(): array
    {
        if ($this->hideActivityColumn) {
            return [
                'Nomor',
                'Jam',
                'Lokasi',
                'Nama',
                'Jenis Makam',
                'Zona',
                'No. Lot',
                'Tenda',
                'Kursi',
                'Tong Bakar',
                'Meja Sembahyang',
                'Lampu',
            ];
        }

        return [
            'Nomor',
            'Jenis Kegiatan',
            'Jam',
            'Lokasi',
            'Nama',
            'Jenis Makam',
            'Zona',
            'No. Lot',
            'Tenda',
            'Kursi',
            'Tong Bakar',
            'Meja Sembahyang',
            'Lampu',
        ];
    }

    private function columnCount(): int
    {
        return $this->hideActivityColumn ? 12 : 13;
    }

    private function facilityStartIndex(): int
    {
        // 0-based array index for the first facility column (Tenda)
        return $this->hideActivityColumn ? 7 : 8;
    }

    private function totalLabelIndex(): int
    {
        // 0-based index of the "Total Kebutuhan Fasilitas:" cell
        return $this->hideActivityColumn ? 6 : 7;
    }

    private function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex($this->columnCount());
    }

    private function facilityStartColLetter(): string
    {
        return Coordinate::stringFromColumnIndex($this->facilityStartIndex() + 1);
    }

    private function formatRange(): string
    {
        if ($this->minDate && $this->maxDate && $this->minDate !== $this->maxDate) {
            return $this->formatIdDate($this->minDate).' - '.$this->formatIdDate($this->maxDate);
        }
        if ($this->minDate) {
            return $this->formatIdDate($this->minDate);
        }
        return now()->timezone('Asia/Jakarta')->format('d M Y');
    }

    private function formatIdDate(string $ymd): string
    {
        $dt = CarbonImmutable::parse($ymd, 'Asia/Jakarta');
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $dt->format('d').' '.$months[(int) $dt->format('n')].' '.$dt->format('Y');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestDataRow();
                $lastCol = $this->lastColLetter();
                $facilityStart = $this->facilityStartColLetter();

                // Merge title rows A1:last and A2:last.
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->getStyle('A1:A2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Column widths (approx).
                $widths = $this->hideActivityColumn
                    ? ['A' => 6, 'B' => 14, 'C' => 16, 'D' => 18, 'E' => 12, 'F' => 8, 'G' => 10, 'H' => 7, 'I' => 7, 'J' => 10, 'K' => 14, 'L' => 8]
                    : ['A' => 6, 'B' => 14, 'C' => 14, 'D' => 16, 'E' => 18, 'F' => 12, 'G' => 8, 'H' => 10, 'I' => 7, 'J' => 7, 'K' => 10, 'L' => 14, 'M' => 8];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Style section headings + merge "Fasilitas" title (facilityStart..lastCol).
                for ($r = 3; $r <= $highestRow; $r++) {
                    $a = (string) $sheet->getCell('A'.$r)->getValue();
                    $facVal = (string) $sheet->getCell($facilityStart.$r)->getValue();
                    if (in_array($a, ['Data Ziarah', 'Data Kegiatan'], true)) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
                        if ($facVal === 'Fasilitas') {
                            $sheet->mergeCells("{$facilityStart}{$r}:{$lastCol}{$r}");
                            $sheet->getStyle("{$facilityStart}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    $headerA = (string) $sheet->getCell('A'.$r)->getValue();
                    $headerB = (string) $sheet->getCell('B'.$r)->getValue();
                    if ($headerA === 'Nomor' && ($headerB === 'Jenis Kegiatan' || $headerB === 'Jam')) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF7F8FA');
                    }
                }

                // Borders for whole used range.
                $sheet->getStyle("A1:{$lastCol}{$highestRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('FFB0B6C6'));
            },
        ];
    }
}

