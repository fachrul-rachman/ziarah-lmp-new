<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitation_plans', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->unique();
            $table->string('public_token', 64)->unique();
            $table->string('applicant_name');
            $table->string('applicant_phone', 32);
            $table->string('relationship_to_deceased', 100);
            $table->string('deceased_name');
            $table->date('date_of_death');
            $table->timestamp('burial_at');
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('visitation_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('planned_moment', 20)->nullable();
            $table->timestamp('whatsapp_consented_at')->nullable();
            $table->string('customer_email')->nullable()->change();
        });

        Schema::create('booking_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('booking_code');
            $table->string('deduplication_key')->unique();
            $table->string('status', 30)->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_whatsapp_messages');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['visitation_plan_id']);
            $table->dropColumn(['visitation_plan_id', 'planned_moment', 'whatsapp_consented_at']);
        });
        Schema::dropIfExists('visitation_plans');
    }
};
