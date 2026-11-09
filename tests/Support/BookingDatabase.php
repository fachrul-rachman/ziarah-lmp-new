<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function createBookingDatabase(): void
{
    Schema::create('locations', function (Blueprint $t) {
        $t->id();
        $t->string('name');
        $t->timestamps();
    });
    Schema::create('zones', function (Blueprint $t) {
        $t->id();
        $t->foreignId('location_id');
        $t->string('name');
        $t->timestamps();
    });
    Schema::create('lots', function (Blueprint $t) {
        $t->id();
        $t->foreignId('location_id');
        $t->foreignId('zone_id');
        $t->string('grave_type');
        $t->string('lot_number');
        $t->string('normalized_lot_number');
        $t->string('size');
        $t->string('normalized_size');
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('time_slots', function (Blueprint $t) {
        $t->id();
        $t->time('start_time');
        $t->timestamps();
    });
    Schema::create('settings', function (Blueprint $t) {
        $t->string('key')->primary();
        $t->text('value');
        $t->timestamps();
    });
    Schema::create('lot_size_rules', function (Blueprint $t) {
        $t->string('normalized_size')->primary();
    });
    Schema::create('events', function (Blueprint $t) {
        $t->id();
        $t->string('name');
        $t->date('start_date');
        $t->date('end_date');
    });
    Schema::create('event_locations', function (Blueprint $t) {
        $t->foreignId('event_id');
        $t->foreignId('location_id');
    });
    Schema::create('event_hidden_facilities', function (Blueprint $t) {
        $t->foreignId('event_id');
        $t->string('facility_key');
    });
    Schema::create('bookings', function (Blueprint $t) {
        $t->id();
        $t->string('public_token')->unique();
        $t->string('activity_type');
        $t->string('booking_code');
        $t->string('customer_name');
        $t->string('customer_email');
        $t->string('customer_phone')->nullable();
        $t->text('additional_note')->nullable();
        $t->timestamp('ethics_consented_at')->nullable();
        $t->foreignId('location_id');
        $t->foreignId('zone_id');
        $t->foreignId('lot_id');
        $t->string('grave_type');
        $t->date('visit_date');
        $t->foreignId('time_slot_id');
        $t->string('status');
        $t->text('cancel_reason')->nullable();
        $t->timestamps();
    });
    DB::statement("CREATE UNIQUE INDEX bookings_unique_active_lot_per_slot ON bookings (visit_date, time_slot_id, lot_id) WHERE status IN ('confirmed', 'rescheduled')");
    Schema::create('booking_facilities', function (Blueprint $t) {
        $t->id();
        $t->foreignId('booking_id')->unique();
        $t->integer('chairs_count');
        $t->integer('burn_barrels_count');
        $t->boolean('has_tent');
        $t->boolean('has_prayer_table');
        $t->boolean('has_lamp');
        $t->timestamps();
    });
    Schema::create('booking_reschedule_histories', function (Blueprint $t) {
        $t->id();
        $t->foreignId('booking_id');
        foreach (['old', 'new'] as $prefix) {
            $t->date($prefix.'_visit_date');
            foreach (['time_slot_id', 'location_id', 'zone_id', 'lot_id'] as $field) {
                $t->unsignedBigInteger($prefix.'_'.$field);
            }
            $t->string($prefix.'_grave_type');
            $t->text($prefix.'_facilities_json');
        }
        $t->timestamp('changed_at');
        $t->timestamps();
    });
    (require database_path('migrations/2026_11_09_000001_create_visitation_plans.php'))->up();
    DB::table('locations')->insert(['id' => 1, 'name' => 'Karawang']);
    DB::table('zones')->insert(['id' => 1, 'location_id' => 1, 'name' => 'A']);
    DB::table('lots')->insert(['id' => 1, 'location_id' => 1, 'zone_id' => 1, 'grave_type' => 'makam',
        'lot_number' => 'A-123', 'normalized_lot_number' => 'a-123', 'size' => '2x3', 'normalized_size' => '2x3']);
    DB::table('time_slots')->insert([
        ['id' => 1, 'start_time' => '09:00:00'], ['id' => 2, 'start_time' => '09:30:00'],
        ['id' => 3, 'start_time' => '10:00:00'], ['id' => 4, 'start_time' => '23:30:00'],
        ['id' => 5, 'start_time' => '00:00:00'],
    ]);
}
