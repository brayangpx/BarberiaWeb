<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('user_shared_id')->nullable()->index();
            $table->string('client_shared_id')->nullable()->index();
            $table->string('haircut_style_shared_id')->nullable()->index();
            $table->string('appointment_type', 30);
            $table->date('appointment_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('final_price', 10, 2);
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
