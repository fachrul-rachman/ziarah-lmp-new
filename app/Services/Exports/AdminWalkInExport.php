<?php

namespace App\Services\Exports;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminWalkInExport implements FromArray, WithEvents
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly ?string $minDate,
        private readonly ?string $maxDate,
    ) {}

    public function array(): array
    {
        $output = [
            ['DATA WALK-IN', '', '', '', '', ''],
            ['PERIODE: '.$this->formatRange(), '', '', '', '', ''],
            ['Nomor', 'Nama', 'Nomor Telepon', 'Nomor Lot', 'Waktu Kedatangan', 'Waktu Persetujuan'],
        ];

        foreach ($this->rows as $index => $row) {
            $output[] = [
                (string) ($index + 1),
                (string) ($row['customer_name'] ?? ''),
                (string) ($row['customer_phone'] ?? ''),
                (string) ($row['lot_number'] ?? ''),
                (string) ($row['visited_at'] ?? ''),
                (string) ($row['ethics_consented_at'] ?? ''),
            ];
        }

        return $output;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(3, $sheet->getHighestDataRow());

                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A3:F3')->getFont()->setBold(true);
                $sheet->getStyle('A3:F3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF7F8FA');
                $sheet->getStyle("A1:F{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                foreach (['A' => 8, 'B' => 24, 'C' => 20, 'D' => 14, 'E' => 24, 'F' => 24] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
            },
        ];
    }

    private function formatRange(): string
    {
        if ($this->minDate && $this->maxDate && $this->minDate !== $this->maxDate) {
            return $this->formatDate($this->minDate).' - '.$this->formatDate($this->maxDate);
        }

        return $this->formatDate($this->minDate ?? now('Asia/Jakarta')->format('Y-m-d'));
    }

    private function formatDate(string $date): string
    {
        return CarbonImmutable::parse($date, 'Asia/Jakarta')->translatedFormat('d F Y');
    }
}
