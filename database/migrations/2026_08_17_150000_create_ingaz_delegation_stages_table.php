<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingaz_delegation_stages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        DB::table('ingaz_delegation_stages')->insert([
            [
                'key' => 'under_process',
                'name_ar' => 'تحت المعالجة',
                'name_en' => 'Under Process',
                'sort_order' => 10,
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'executed',
                'name_ar' => 'تم تنفيذ التفويض',
                'name_en' => 'Delegation Executed',
                'sort_order' => 100,
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ingaz_delegation_stages');
    }
};
