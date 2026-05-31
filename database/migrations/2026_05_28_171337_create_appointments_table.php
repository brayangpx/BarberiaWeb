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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('haircut_style_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('appointment_type', 30);
            $table->date('appointment_date');
            $table->time('start_time');

            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->decimal('final_price', 10, 2);
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();

            $table->dateTime('reminder_at')->nullable();
            $table->string('reminder_status', 30)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
