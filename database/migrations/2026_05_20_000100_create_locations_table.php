<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX locations_name_lower_unique ON locations (lower(name));');
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

