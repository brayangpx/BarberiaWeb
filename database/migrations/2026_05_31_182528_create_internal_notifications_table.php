<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('appointment_shared_id')->unique();
            $table->string('title');
            $table->text('message');
            $table->dateTime('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
    }
};
