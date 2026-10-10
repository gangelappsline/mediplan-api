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
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone', 60)->default('America/Mexico_City');
            $table->unsignedSmallInteger('appointment_duration_minutes')->default(30);
            $table->unsignedSmallInteger('slot_interval_minutes')->default(30);
            $table->unsignedSmallInteger('min_notice_minutes')->default(60);
            $table->unsignedSmallInteger('max_advance_days')->default(30);
            $table->json('working_hours')->nullable();
            $table->boolean('auto_confirm_appointments')->default(false);
            $table->boolean('allow_online_booking')->default(true);
            $table->string('currency', 3)->default('MXN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
