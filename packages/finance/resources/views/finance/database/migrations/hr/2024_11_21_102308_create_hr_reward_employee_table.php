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
        Schema::create('hr_reward_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
            $table->foreignId('reward_id')->references('id')->on('hr_rewards')->onDelete('cascade');
            $table->double('salary')->default(0);
            $table->string('exchange_timing');
            $table->date('date');
            $table->double('value')->default(0);
            $table->double('net_value')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_reward_employee');
    }
};
