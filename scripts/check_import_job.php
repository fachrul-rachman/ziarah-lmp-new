<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = (int) ($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Usage: php scripts/check_import_job.php <import_job_id>\n");
    exit(2);
}

$job = App\Models\ImportJob::query()->find($id);
if (! $job) {
    fwrite(STDERR, "ImportJob {$id} not found\n");
    exit(1);
}

$jobsCount = Illuminate\Support\Facades\DB::table('jobs')->count();
$failedJobsCount = Illuminate\Support\Facades\DB::table('failed_jobs')->count();

echo "import_job_id={$job->id} status={$job->status} total_rows={$job->total_rows} processed={$job->processed_rows} success={$job->success_rows} failed={$job->failed_rows}\n";
echo "queue jobs={$jobsCount} failed_jobs={$failedJobsCount}\n";

