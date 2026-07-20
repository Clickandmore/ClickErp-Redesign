<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_contract_extensions') || Schema::hasColumn('rental_contract_extensions', 'extension_value')) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            $table->decimal('extension_value', 12, 2)->nullable()->after('extension_days');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_extensions') || !Schema::hasColumn('rental_contract_extensions', 'extension_value')) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            $table->dropColumn('extension_value');
        });
    }
};
