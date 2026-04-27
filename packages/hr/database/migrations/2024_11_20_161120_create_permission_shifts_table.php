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
        Schema::create('hr_permission_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->references('id')->on('hr_perrmission_setting')->onDelete('cascade');
            $table->foreignId('shift_id')->references('id')->on('hr_shifts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_shifts');
    }
};
