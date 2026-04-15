<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_total_amount')) {
                $table->decimal('sponsorship_transfer_total_amount', 12, 2)
                    ->default(0)
                    ->after('sponsorship_transfer_amount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_paid_amount')) {
                $table->decimal('sponsorship_transfer_paid_amount', 12, 2)
                    ->default(0)
                    ->after('sponsorship_transfer_total_amount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_remaining_amount')) {
                $table->decimal('sponsorship_transfer_remaining_amount', 12, 2)
                    ->default(0)
                    ->after('sponsorship_transfer_paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_remaining_amount')) {
                $table->dropColumn('sponsorship_transfer_remaining_amount');
            }

            if (Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_paid_amount')) {
                $table->dropColumn('sponsorship_transfer_paid_amount');
            }

            if (Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_total_amount')) {
                $table->dropColumn('sponsorship_transfer_total_amount');
            }
        });
    }
};
