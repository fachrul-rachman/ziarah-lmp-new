<?php

use App\Models\Lot;
use Database\Seeders\BookingTestSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Support/BookingDatabase.php';

test('small booking seeder adds only missing sample locations and lots and can be rerun', function () {
    createBookingDatabase();
    DB::table('settings')->insert(['key' => 'discord_webhook_url', 'value' => 'preserve-this-value']);

    $this->seed(BookingTestSeeder::class);
    $this->seed(BookingTestSeeder::class);

    expect(DB::table('locations')->count())->toBe(2)
        ->and(DB::table('zones')->count())->toBe(4)
        ->and(Lot::count())->toBe(12)
        ->and(Lot::where('lot_number', '123')->first()->size)->toBe('2x3')
        ->and(Lot::where('lot_number', '124')->first()->normalized_size)->toBe('double')
        ->and(Lot::whereHas('location', fn ($query) => $query->where('name', 'Tangerang'))->count())->toBe(6)
        ->and(DB::table('time_slots')->count())->toBe(9)
        ->and(DB::table('time_slots')->where('start_time', '10:00:00')->count())->toBe(1)
        ->and(DB::table('settings')->where('key', 'discord_webhook_url')->value('value'))->toBe('preserve-this-value');
});
