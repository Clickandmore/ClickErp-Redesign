<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('setting_plans', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 500);
            $table->unsignedBigInteger('setting_id');
            $table->foreign('setting_id')->references('id')->on('settings')->onDelete('cascade');
            $table->timestamps();
        });

        DB::table('setting_plans')->insert([
            ['module_name' => 'main', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_name' => 'finance', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_name' => 'hr', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_name' => 'zatca', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_name' => 'crm', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_name' => 'employee', 'setting_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_plans');
    }
};
