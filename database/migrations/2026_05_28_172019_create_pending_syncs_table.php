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
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(
                ['target_connection', 'table_name', 'record_shared_id'],
                'pending_syncs_unique_record'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_syncs');
    }
};
