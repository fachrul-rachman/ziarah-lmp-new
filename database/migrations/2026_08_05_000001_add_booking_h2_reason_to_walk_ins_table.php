<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('walk_ins', function (Blueprint $table): void {
            $table->string('booking_h2_reason', 50)->nullable()->after('lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('walk_ins', function (Blueprint $table): void {
            $table->dropColumn('booking_h2_reason');
        });
    }
};
