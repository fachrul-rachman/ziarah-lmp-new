<?php

namespace App\Imports\ReadFilters;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class LotImportReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $maxColumnIndex = 5,
        private readonly int $headerRow = 1,
    ) {
    }

    public function readCell($column, $row, $worksheetName = ''): bool
    {
        // Always allow header row so WithHeadingRow can map keys correctly.
        if ((int) $row === $this->headerRow) {
            return true;
        }

        // Limit columns to A..maxColumnIndex (default A-E).
        try {
            $columnIndex = Coordinate::columnIndexFromString((string) $column);
        } catch (\Throwable) {
            return false;
        }

        return $columnIndex <= $this->maxColumnIndex;
    }
}

