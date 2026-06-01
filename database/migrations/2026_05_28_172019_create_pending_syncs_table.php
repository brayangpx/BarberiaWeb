<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('target_connection');
            $table->string('table_name');
            $table->string('operation');
            $table->string('record_shared_id')->nullable()->index();
            $table->json('payload');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_syncs');
    }
};
