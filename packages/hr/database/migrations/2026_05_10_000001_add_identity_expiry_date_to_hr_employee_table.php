<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employee', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable()->after('national_num');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employee', 'identity_expiry_date')) {
                $table->dropColumn('identity_expiry_date');
            }
        });
    }
};
