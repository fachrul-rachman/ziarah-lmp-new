<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Export Storage Disk
    |--------------------------------------------------------------------------
    |
    | Export jobs are processed asynchronously. In production, the web server and
    | queue workers may run on different machines/containers, so exports must be
    | stored on a shared disk (e.g. S3) to make downloads reliable.
    |
    */
    // Default to the dedicated private exports disk.
    'disk' => env('EXPORTS_DISK', 'exports'),

    /*
    |--------------------------------------------------------------------------
    | Export Queue
    |--------------------------------------------------------------------------
    |
    | Which queue connection + queue name should process export jobs.
    | In local development we default to "sync" so exports are generated
    | immediately and downloaded from the same machine.
    |
    */
    'queue_connection' => env(
        'EXPORTS_QUEUE_CONNECTION',
        env('APP_ENV') === 'local' ? 'sync' : env('QUEUE_CONNECTION', 'database')
    ),

    'queue' => env('EXPORTS_QUEUE', 'exports'),
];
