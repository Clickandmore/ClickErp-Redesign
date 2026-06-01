<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_custody_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('hr_employee_custodies', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('account_id')
                ->constrained('hr_custody_locations')
                ->nullOnDelete();
        });

        $locationId = DB::table('hr_custody_locations')->insertGetId([
            'name' => 'المقر الرئيسي',
            'code' => 'MAIN',
            'address' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hr_employee_custodies')
            ->whereNull('location_id')
            ->update(['location_id' => $locationId]);
    }

    public function down(): void
    {
        Schema::table('hr_employee_custodies', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::dropIfExists('hr_custody_locations');
    }
};
