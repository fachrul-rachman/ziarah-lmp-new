<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$limit = (int) ($argv[1] ?? 5);
if ($limit <= 0) {
    $limit = 5;
}

$rows = Illuminate\Support\Facades\DB::table('failed_jobs')
    ->orderByDesc('id')
    ->limit($limit)
    ->get(['id', 'uuid', 'queue', 'failed_at', 'exception']);

foreach ($rows as $row) {
    echo "id={$row->id} uuid={$row->uuid} queue={$row->queue} failed_at={$row->failed_at}\n";
    $firstLine = strtok((string) $row->exception, "\n") ?: '';
    echo "exception={$firstLine}\n\n";
}

