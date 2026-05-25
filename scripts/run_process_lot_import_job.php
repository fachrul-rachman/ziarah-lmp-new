<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$importJobId = (int) ($argv[1] ?? 0);
$storedPath = $argv[2] ?? null;
if ($importJobId <= 0 || ! $storedPath) {
    fwrite(STDERR, "Usage: php scripts/run_process_lot_import_job.php <import_job_id> <stored_path>\n");
    exit(2);
}

try {
    $job = new App\Jobs\ProcessLotImportJob($importJobId, $storedPath);
    $job->handle();
    echo "OK\n";
} catch (Throwable $e) {
    echo "ERROR: ".$e::class.' '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}

