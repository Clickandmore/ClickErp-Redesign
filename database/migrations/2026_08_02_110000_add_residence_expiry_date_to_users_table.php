<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تاريخ انتهاء الإقامة للعميل غير السعودي — اختياري.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'residence_expiry_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->date('residence_expiry_date')->nullable()->after('identity_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'residence_expiry_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('residence_expiry_date');
        });
    }
};
