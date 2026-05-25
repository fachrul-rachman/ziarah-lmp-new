<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->unsignedSmallInteger('chairs_count');
            $table->unsignedSmallInteger('burn_barrels_count');
            $table->boolean('has_tent')->default(false);
            $table->boolean('has_prayer_table')->default(false);
            $table->boolean('has_lamp')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_facilities');
    }
};

