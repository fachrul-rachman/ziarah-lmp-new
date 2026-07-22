<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walk_ins', function (Blueprint $table): void {
            $table->id();
            $table->string('public_token')->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 15);
            $table->string('lot_number', 10)->nullable();
            $table->timestamp('ethics_consented_at');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_ins');
    }
};
