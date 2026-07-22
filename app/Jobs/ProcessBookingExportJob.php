<?php

namespace App\Jobs;

use App\Models\ExportJob;
use App\Services\ExportReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBookingExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $exportJobId) {}

    public function handle(ExportReportService $service): void
    {
        $exportJob = ExportJob::query()->find($this->exportJobId);
        if (! $exportJob) {
            return;
        }

        $exportJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $disk = $exportJob->disk ?: (config('exports.disk') ?? config('filesystems.default'));
            $filters = (array) $exportJob->filters_json;
            $filePath = ($filters['record_type'] ?? 'booking') === 'walk_in'
                ? $service->exportWalkIns($exportJob->format, $filters, $disk)
                : $service->exportBookings($exportJob->format, $filters, $disk);
            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}
