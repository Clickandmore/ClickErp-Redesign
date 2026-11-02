<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «النوع» كما يحدده المحاسب لحساب بعينه (مثل «مصروفات عمولات»)، ويظهر في إدارة الدليل وتصديرها بدل النوع المحسوب.
 * فارغ = العرض كما كان. لا يغيّر نوع الحساب (type) الذي يعتمد عليه منطق البرنامج.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fn_accounts') && !Schema::hasColumn('fn_accounts', 'type_label')) {
            Schema::table('fn_accounts', function (Blueprint $table) {
                $table->string('type_label')->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fn_accounts', 'type_label')) {
            Schema::table('fn_accounts', function (Blueprint $table) {
                $table->dropColumn('type_label');
            });
        }
    }
};
