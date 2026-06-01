<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        foreach (config('superadmin_modules', []) as $key => $module) {
            DB::table('system_module_settings')->insert([
                'key' => $key,
                'name' => $module['name'] ?? $key,
                'description' => $module['description'] ?? null,
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_module_settings');
    }
};
