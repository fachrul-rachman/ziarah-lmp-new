<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE events ADD CONSTRAINT events_date_range_check CHECK (end_date >= start_date);');
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

