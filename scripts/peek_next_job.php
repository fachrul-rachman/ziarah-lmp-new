<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$row = Illuminate\Support\Facades\DB::table('jobs')->orderBy('id')->first();
if (! $row) {
    echo "No jobs in queue\n";
    exit(0);
}

$payload = json_decode($row->payload, true);
$displayName = $payload['displayName'] ?? null;
echo "job_id={$row->id} queue={$row->queue} attempts={$row->attempts} available_at={$row->available_at} reserved_at={$row->reserved_at}\n";
echo "displayName={$displayName}\n";

