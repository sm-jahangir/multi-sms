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
        Schema::create('sms_autoresponders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type'); // keyword, incoming_sms, missed_call, webhook
            $table->json('trigger_value')->nullable();
            $table->text('response_message')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('delay_minutes')->default(0);
            $table->integer('max_triggers_per_number')->default(0); // 0 = unlimited
            $table->json('conditions')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['is_active']);
            $table->index(['trigger_type']);
            $table->index(['template_id']);
            $table->index(['trigger_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_autoresponders');
    }
};