<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('normalized_size')->nullable()->after('size');
        });

        // Backfill with case-insensitive normalized key and title case display.
        // normalize: trim + collapse spaces; key: lower; display: initcap.
        DB::statement("
            UPDATE lots
            SET
                size = initcap(regexp_replace(trim(size), '\\\\s+', ' ', 'g')),
                normalized_size = lower(regexp_replace(trim(size), '\\\\s+', ' ', 'g'))
        ");

        Schema::table('lots', function (Blueprint $table) {
            $table->string('normalized_size')->nullable(false)->change();
            $table->index(['normalized_size']);
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex(['normalized_size']);
            $table->dropColumn('normalized_size');
        });
    }
};

