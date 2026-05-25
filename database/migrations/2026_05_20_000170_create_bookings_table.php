<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('public_token')->unique();
            $table->string('activity_type');
            $table->string('booking_code');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('zone_id')->constrained('zones')->restrictOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->restrictOnDelete();
            $table->string('grave_type');
            $table->date('visit_date');
            $table->foreignId('time_slot_id')->constrained('time_slots')->restrictOnDelete();
            $table->string('status');
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['visit_date', 'time_slot_id']);
            $table->index(['status']);
        });

        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_activity_type_check CHECK (activity_type IN ('ziarah', 'naik_batu', 'start_work', 'wang_san'));");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_grave_type_check CHECK (grave_type IN ('makam', 'kotak_abu'));");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_check CHECK (status IN ('confirmed', 'rescheduled', 'cancelled', 'completed'));");
        DB::statement("CREATE UNIQUE INDEX bookings_unique_active_lot_per_slot ON bookings (visit_date, time_slot_id, lot_id) WHERE status IN ('confirmed', 'rescheduled');");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
