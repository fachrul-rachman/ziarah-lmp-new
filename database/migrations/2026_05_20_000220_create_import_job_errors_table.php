<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_job_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained('import_jobs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->jsonb('raw_data_json');
            $table->text('error_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_job_errors');
    }
};

