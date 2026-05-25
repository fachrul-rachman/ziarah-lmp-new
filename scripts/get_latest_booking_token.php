<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = Illuminate\Support\Facades\DB::table('bookings')->orderByDesc('id')->value('public_token');
echo ($token ?: '')."\n";

