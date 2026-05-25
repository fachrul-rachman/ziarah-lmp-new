<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$settings = DB::table('settings')
    ->whereIn('key', ['discord_webhook_url', 'discord_notification_time'])
    ->pluck('value', 'key')
    ->all();

echo "SETTINGS\n";
var_export($settings);
echo "\n\n";

echo "NOTIFICATION_LOGS (latest 10)\n";
$logs = DB::table('notification_logs')->orderByDesc('id')->limit(10)->get();
foreach ($logs as $l) {
    echo json_encode($l, JSON_UNESCAPED_UNICODE), "\n";
}

echo "\nJOBS count=", DB::table('jobs')->count(), "\n";
$jobs = DB::table('jobs')->orderByDesc('id')->limit(10)->get(['id', 'queue', 'attempts', 'available_at', 'created_at', 'payload']);
foreach ($jobs as $j) {
    $payload = json_decode($j->payload, true) ?: [];
    $display = $payload['displayName'] ?? null;
    $commandName = $payload['data']['commandName'] ?? null;
    echo "job id={$j->id} queue={$j->queue} attempts={$j->attempts} display={$display} command={$commandName}\n";
}

echo "\nFAILED_JOBS count=", DB::table('failed_jobs')->count(), "\n";
$failed = DB::table('failed_jobs')->orderByDesc('id')->limit(5)->get(['id', 'failed_at', 'exception']);
foreach ($failed as $f) {
    echo "failed id={$f->id} failed_at={$f->failed_at}\n";
}

