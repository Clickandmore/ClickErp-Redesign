<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('maids', 'driving_license_file')) {
            Schema::table('maids', function (Blueprint $table) {
                // مرفق رخصة القيادة (اختياري) — يُعرض في الموقع الخارجي مكان رمز QR عند وجوده.
                $table->string('driving_license_file')->nullable()->after('has_driving_license');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('maids', 'driving_license_file')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('driving_license_file');
            });
        }
    }
};
