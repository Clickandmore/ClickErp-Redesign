<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('rental_contract_infos')
            || Schema::hasColumn('rental_contract_infos', 'package_price_basis')
        ) {
            return;
        }

        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->string('package_price_basis', 20)
                ->nullable()
                ->after('package_total_with_tax');
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('rental_contract_infos')
            && Schema::hasColumn('rental_contract_infos', 'package_price_basis')
        ) {
            Schema::table('rental_contract_infos', function (Blueprint $table) {
                $table->dropColumn('package_price_basis');
            });
        }
    }
};
