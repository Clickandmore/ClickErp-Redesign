<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_contract_infos')) {
            return;
        }

        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'closure_return_reason')) {
                $table->text('closure_return_reason')->nullable()->after('closure_client_iban_attachment_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_infos')) {
            return;
        }

        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contract_infos', 'closure_return_reason')) {
                $table->dropColumn('closure_return_reason');
            }
        });
    }
};
