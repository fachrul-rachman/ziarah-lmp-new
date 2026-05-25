<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_size_rules', function (Blueprint $table) {
            $table->string('normalized_size')->primary();
            $table->string('display_size');

            $table->unsignedSmallInteger('chairs_min')->default(5);
            $table->unsignedSmallInteger('chairs_max')->default(10);
            $table->unsignedSmallInteger('burn_barrels_min')->default(0);
            $table->unsignedSmallInteger('burn_barrels_max')->default(2);

            $table->boolean('tent_allowed')->default(true);
            $table->boolean('prayer_table_allowed')->default(true);
            $table->boolean('lamp_allowed')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_size_rules');
    }
};

