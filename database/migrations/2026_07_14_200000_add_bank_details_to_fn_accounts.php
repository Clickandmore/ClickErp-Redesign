<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_accounts')) {
            return;
        }

        Schema::table('fn_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('fn_accounts', 'bank_number')) {
                $table->string('bank_number')->nullable()->after('name');
            }

            if (!Schema::hasColumn('fn_accounts', 'iban')) {
                $table->string('iban', 34)->nullable()->after('bank_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fn_accounts')) {
            return;
        }

        Schema::table('fn_accounts', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('fn_accounts', 'iban')) {
                $columns[] = 'iban';
            }

            if (Schema::hasColumn('fn_accounts', 'bank_number')) {
                $columns[] = 'bank_number';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
