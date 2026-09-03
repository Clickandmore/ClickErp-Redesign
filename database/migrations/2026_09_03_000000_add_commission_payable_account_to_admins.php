<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admins', 'commission_payable_account_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_payable_account_id')->nullable()->after('admin_type');
            $table->unique('commission_payable_account_id', 'admins_commission_payable_account_unique');
            $table->foreign('commission_payable_account_id', 'admins_commission_payable_account_fk')
                ->references('id')->on('fn_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('admins', 'commission_payable_account_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign('admins_commission_payable_account_fk');
            $table->dropUnique('admins_commission_payable_account_unique');
            $table->dropColumn('commission_payable_account_id');
        });
    }
};
