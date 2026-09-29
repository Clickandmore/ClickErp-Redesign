<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تاريخ انتهاء ترخيص العميل الشركة — يُستخدم لإظهار بادج "ترخيص منتهي".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'license_expiry_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->date('license_expiry_date')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'license_expiry_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('license_expiry_date');
        });
    }
};
