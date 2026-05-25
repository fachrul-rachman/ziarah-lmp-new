<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('format');
            $table->jsonb('filters_json');
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE export_jobs ADD CONSTRAINT export_jobs_status_check CHECK (status IN ('queued', 'processing', 'completed', 'failed'));");
        DB::statement("ALTER TABLE export_jobs ADD CONSTRAINT export_jobs_format_check CHECK (format IN ('excel', 'pdf'));");
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};

