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
        Schema::create('sms_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('autoresponder_id');
            $table->string('phone_number');
            $table->string('trigger_type');
            $table->json('trigger_data')->nullable();
            $table->boolean('response_sent')->default(false);
            $table->string('response_message_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['autoresponder_id']);
            $table->index(['phone_number']);
            $table->index(['trigger_type']);
            $table->index(['response_sent']);
            $table->index(['processed_at']);
            $table->index(['phone_number', 'autoresponder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_triggers');
    }
};