<?php

return [
    'lots_rate_limit_per_minute' => (int) env('BOOKING_LOTS_RATE_LIMIT_PER_MINUTE', 30),
    'lots_rate_limit_store' => env('BOOKING_LOTS_RATE_LIMIT_STORE', 'file'),
    'lots_cache_seconds' => (int) env('BOOKING_LOTS_CACHE_SECONDS', 30),
    'lots_cache_store' => env('BOOKING_LOTS_CACHE_STORE', 'file'),
    'lots_min_days_ahead' => (int) env('BOOKING_LOTS_MIN_DAYS_AHEAD', 2),
    'lots_max_days_ahead' => (int) env('BOOKING_LOTS_MAX_DAYS_AHEAD', 100),
];
