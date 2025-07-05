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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('from')->nullable();
            $table->text('body');
            $table->string('driver');
            $table->string('status'); // sent, failed, pending
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->string('message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'sent_at']);
            $table->index(['driver', 'status']);
            $table->index(['campaign_id']);
            $table->index(['template_id']);
            $table->index(['to']);
            $table->index(['message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};