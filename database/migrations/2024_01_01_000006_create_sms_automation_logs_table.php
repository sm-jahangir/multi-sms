<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('autoresponder_id')->nullable();
            $table->unsignedBigInteger('trigger_id')->nullable();
            $table->string('phone_number');
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable();
            $table->text('response_message')->nullable();
            $table->string('status'); // success, failed, pending
            $table->string('message_id')->nullable();
            $table->string('driver_used')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamps();
            
            $table->index(['autoresponder_id']);
            $table->index(['trigger_id']);
            $table->index(['phone_number']);
            $table->index(['status']);
            $table->index(['trigger_type']);
            $table->index(['driver_used']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_automation_logs');
    }
};