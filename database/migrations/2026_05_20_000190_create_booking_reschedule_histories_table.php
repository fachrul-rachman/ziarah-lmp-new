<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reschedule_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            $table->date('old_visit_date');
            $table->foreignId('old_time_slot_id')->constrained('time_slots')->restrictOnDelete();
            $table->foreignId('old_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('old_zone_id')->constrained('zones')->restrictOnDelete();
            $table->foreignId('old_lot_id')->constrained('lots')->restrictOnDelete();
            $table->string('old_grave_type');
            $table->jsonb('old_facilities_json');

            $table->date('new_visit_date');
            $table->foreignId('new_time_slot_id')->constrained('time_slots')->restrictOnDelete();
            $table->foreignId('new_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('new_zone_id')->constrained('zones')->restrictOnDelete();
            $table->foreignId('new_lot_id')->constrained('lots')->restrictOnDelete();
            $table->string('new_grave_type');
            $table->jsonb('new_facilities_json');

            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reschedule_histories');
    }
};

