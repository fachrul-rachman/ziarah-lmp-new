<?php

use App\Services\DiscordNotificationService;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    foreach (['notification_log_bookings', 'notification_logs', 'booking_facilities', 'bookings', 'lots', 'time_slots', 'zones', 'locations', 'settings'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('settings', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->text('value');
        $table->timestamps();
    });
    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('zones', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('time_slots', function (Blueprint $table) {
        $table->id();
        $table->time('start_time');
    });
    Schema::create('lots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('location_id');
        $table->foreignId('zone_id');
        $table->string('lot_number');
        $table->string('size');
        $table->string('grave_type');
        $table->softDeletes();
    });
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->string('public_token');
        $table->string('activity_type');
        $table->string('booking_code');
        $table->string('customer_name');
        $table->string('customer_email');
        $table->string('customer_phone');
        $table->text('additional_note')->nullable();
        $table->foreignId('location_id');
        $table->foreignId('zone_id');
        $table->foreignId('lot_id');
        $table->string('grave_type');
        $table->date('visit_date');
        $table->foreignId('time_slot_id');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('booking_facilities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('booking_id');
        $table->integer('chairs_count')->default(0);
        $table->integer('burn_barrels_count')->default(0);
        $table->boolean('has_tent')->default(false);
        $table->boolean('has_prayer_table')->default(false);
        $table->boolean('has_lamp')->default(false);
        $table->timestamps();
    });
    Schema::create('notification_logs', function (Blueprint $table) {
        $table->id();
        $table->date('target_date');
        $table->string('scheduled_time', 5)->nullable();
        $table->string('status');
        $table->text('message')->nullable();
        $table->text('attachments_json')->nullable();
        $table->timestamp('sent_at')->nullable();
        $table->timestamps();
    });
    Schema::create('notification_log_bookings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('notification_log_id');
        $table->foreignId('booking_id');
        $table->string('kind');
        $table->string('snapshot_hash', 64);
        $table->timestamps();
    });

    DB::table('settings')->insert(['key' => 'discord_webhook_url', 'value' => 'https://example.test/webhook']);
    DB::table('locations')->insert(['id' => 1, 'name' => 'Tangerang']);
    DB::table('zones')->insert(['id' => 1, 'name' => 'A']);
    DB::table('time_slots')->insert(['id' => 1, 'start_time' => '10:00']);
    DB::table('lots')->insert([
        ['id' => 1, 'location_id' => 1, 'zone_id' => 1, 'lot_number' => 'A-1', 'size' => '2x3', 'grave_type' => 'makam'],
        ['id' => 2, 'location_id' => 1, 'zone_id' => 1, 'lot_number' => 'A-2', 'size' => '2x3', 'grave_type' => 'makam'],
    ]);

    Storage::fake('local');
    $mock = new MockHandler([new Response(204), new Response(204)]);
    app()->instance(Client::class, new Client(['handler' => HandlerStack::create($mock)]));
});

test('later Discord batches contain only new bookings and separate changes', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 08:00:00', 'Asia/Jakarta'));
    $targetDate = '2026-08-15';

    $firstBookingId = createBatchBooking(1, 'BK-1', $targetDate);
    $firstLogId = DB::table('notification_logs')->insertGetId([
        'target_date' => $targetDate,
        'scheduled_time' => '08:00',
        'status' => 'processing',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $first = app(DiscordNotificationService::class)->sendForTargetDate($targetDate, $firstLogId);
    DB::table('notification_logs')->where('id', $firstLogId)->update(['status' => 'sent', 'sent_at' => now()]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 14:00:00', 'Asia/Jakarta'));
    DB::table('bookings')->where('id', $firstBookingId)->update(['status' => 'cancelled', 'updated_at' => now()]);
    $secondBookingId = createBatchBooking(2, 'BK-2', $targetDate);
    $secondLogId = DB::table('notification_logs')->insertGetId([
        'target_date' => $targetDate,
        'scheduled_time' => '14:00',
        'status' => 'processing',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $second = app(DiscordNotificationService::class)->sendForTargetDate($targetDate, $secondLogId);
    $secondKinds = DB::table('notification_log_bookings')
        ->where('notification_log_id', $secondLogId)
        ->pluck('kind', 'booking_id');

    expect($first['message'])->toContain('Total Booking: 1')
        ->and($second['message'])->toContain('Total Booking: 1')
        ->and($second['message'])->toContain('Total Perubahan: 1')
        ->and($secondKinds[$firstBookingId])->toBe('changed')
        ->and($secondKinds[$secondBookingId])->toBe('new');

    CarbonImmutable::setTestNow();
});

test('Discord command follows one or multiple admin report times', function () {
    Bus::fake();
    DB::table('settings')->insert([
        'key' => 'discord_notification_times',
        'value' => json_encode(['08:00', '14:00']),
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 08:00:00', 'Asia/Jakarta'));
    Artisan::call('discord:notify-h1');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 09:00:00', 'Asia/Jakarta'));
    Artisan::call('discord:notify-h1');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 14:00:00', 'Asia/Jakarta'));
    Artisan::call('discord:notify-h1');

    expect(DB::table('notification_logs')->orderBy('scheduled_time')->pluck('scheduled_time')->all())
        ->toBe(['08:00', '14:00']);

    CarbonImmutable::setTestNow();
});

function createBatchBooking(int $lotId, string $code, string $visitDate): int
{
    $id = DB::table('bookings')->insertGetId([
        'public_token' => $code,
        'activity_type' => 'ziarah',
        'booking_code' => $code,
        'customer_name' => $code,
        'customer_email' => strtolower($code).'@example.com',
        'customer_phone' => '6281234567890',
        'location_id' => 1,
        'zone_id' => 1,
        'lot_id' => $lotId,
        'grave_type' => 'makam',
        'visit_date' => $visitDate,
        'time_slot_id' => 1,
        'status' => 'confirmed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('booking_facilities')->insert([
        'booking_id' => $id,
        'chairs_count' => 5,
        'burn_barrels_count' => 1,
        'has_tent' => true,
        'has_prayer_table' => false,
        'has_lamp' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}
