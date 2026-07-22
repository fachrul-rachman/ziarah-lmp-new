<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('ethics_consented_at')->nullable()->after('additional_note');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('ethics_consented_at');
        });
    }
};
