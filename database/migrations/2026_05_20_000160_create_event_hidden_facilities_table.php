<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_hidden_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('facility_key');
            $table->timestamps();

            $table->unique(['event_id', 'facility_key']);
        });

        DB::statement("ALTER TABLE event_hidden_facilities ADD CONSTRAINT event_hidden_facilities_facility_key_check CHECK (facility_key IN ('chairs', 'burn_barrels', 'tent', 'prayer_table', 'lamp'));");
    }

    public function down(): void
    {
        Schema::dropIfExists('event_hidden_facilities');
    }
};

