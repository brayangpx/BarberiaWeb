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
        Schema::create('haircut_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('base_price', 10, 2);
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable();
            $table->text('ai_prompt_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('haircut_styles');
    }
};
