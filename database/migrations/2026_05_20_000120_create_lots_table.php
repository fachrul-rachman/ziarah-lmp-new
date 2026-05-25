<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('grave_type');
            $table->string('lot_number');
            $table->string('normalized_lot_number');
            $table->string('size');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['location_id', 'zone_id', 'grave_type']);
        });

        DB::statement("ALTER TABLE lots ADD CONSTRAINT lots_grave_type_check CHECK (grave_type IN ('makam', 'kotak_abu'));");
        DB::statement("CREATE UNIQUE INDEX lots_unique_active ON lots (grave_type, location_id, zone_id, normalized_lot_number) WHERE deleted_at IS NULL;");
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};

