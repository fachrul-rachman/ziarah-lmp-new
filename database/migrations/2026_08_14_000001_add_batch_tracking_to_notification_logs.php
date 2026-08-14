<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->string('scheduled_time', 5)->nullable()->after('target_date');
            $table->unique(['target_date', 'scheduled_time']);
        });

        Schema::create('notification_log_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('snapshot_hash', 64);
            $table->timestamps();

            $table->unique(['notification_log_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_log_bookings');

        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->dropUnique(['target_date', 'scheduled_time']);
            $table->dropColumn('scheduled_time');
        });
    }
};
