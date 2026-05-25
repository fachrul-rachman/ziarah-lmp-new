<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$storedPath = $argv[1] ?? 'imports/lot-import-test.xlsx';

$job = App\Models\ImportJob::query()->create([
    'filename' => basename($storedPath),
    'status' => 'queued',
    'total_rows' => 0,
    'processed_rows' => 0,
    'success_rows' => 0,
    'failed_rows' => 0,
    'started_at' => null,
    'finished_at' => null,
]);

App\Jobs\ProcessLotImportJob::dispatch($job->id, $storedPath);

echo "Queued import_job_id={$job->id} storedPath={$storedPath}\n";

