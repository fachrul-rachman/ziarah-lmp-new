<?php

namespace App\Jobs;

use App\Imports\LotExcelImport;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessLotImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $importJobId,
        public readonly string $storedPath,
    ) {
    }

    public function handle(): void
    {
        // Best-effort: keep imports stable on low default memory (128MB).
        @ini_set('memory_limit', '512M');
        gc_enable();

        $importJob = ImportJob::query()->find($this->importJobId);
        if (! $importJob) {
            return;
        }

        $importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $fullPath = Storage::path($this->storedPath);

        try {
            $importJob->update([
                'total_rows' => $this->countRows($fullPath),
            ]);

            Excel::queueImport(new LotExcelImport($importJob->id), $fullPath)
                ->allOnQueue($this->queue ?? 'default');
        } catch (\Throwable $e) {
            $importJob->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            throw $e;
        } finally {
            gc_collect_cycles();
        }
    }

    private function countRows(string $fullPath): int
    {
        $reader = IOFactory::createReaderForFile($fullPath);

        // Use worksheet metadata to avoid loading the full spreadsheet into memory.
        $infos = $reader->listWorksheetInfo($fullPath);
        $totalRows = (int) (($infos[0]['totalRows'] ?? 0));

        // Assume first row is header.
        return max(0, $totalRows - 1);
    }
}
