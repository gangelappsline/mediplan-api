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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_appointment_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'name']);
            $table->index(['business_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
