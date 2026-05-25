<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$storedPath = $argv[1] ?? 'imports/lot-import-test.xlsx';

$importJob = App\Models\ImportJob::query()->create([
    'filename' => basename($storedPath),
    'status' => 'queued',
    'total_rows' => 0,
    'processed_rows' => 0,
    'success_rows' => 0,
    'failed_rows' => 0,
    'started_at' => null,
    'finished_at' => null,
]);

echo "import_job_id={$importJob->id}\n";

(new App\Jobs\ProcessLotImportJob($importJob->id, $storedPath))->handle();

echo "Queued chunk jobs. Now run worker in another terminal:\n";
echo "php artisan queue:work --stop-when-empty --memory=1024\n";

