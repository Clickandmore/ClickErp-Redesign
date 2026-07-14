<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->string('legacy_source', 100)->nullable()->index()->after('cost_center_id');
            $table->string('legacy_source_file')->nullable()->after('legacy_source');
            $table->string('legacy_source_sheet')->nullable()->after('legacy_source_file');
            $table->unsignedInteger('legacy_source_row')->nullable()->after('legacy_source_sheet');
            $table->unsignedInteger('legacy_sequence')->nullable()->after('legacy_source_row');
            $table->string('legacy_hijri_date', 50)->nullable()->after('legacy_sequence');
            $table->decimal('legacy_total', 18, 4)->nullable()->after('legacy_hijri_date');
            $table->string('legacy_currency', 100)->nullable()->after('legacy_total');
            $table->decimal('legacy_financial_movement', 18, 4)->nullable()->after('legacy_currency');
            $table->string('legacy_financial_movement_currency', 100)->nullable()->after('legacy_financial_movement');
            $table->unsignedBigInteger('legacy_client_number')->nullable()->index()->after('legacy_financial_movement_currency');
            $table->unsignedBigInteger('legacy_order_number')->nullable()->index()->after('legacy_client_number');
            $table->unsignedBigInteger('recruitment_contract_id')->nullable()->after('legacy_order_number');
            $table->unsignedBigInteger('rental_contract_id')->nullable()->after('recruitment_contract_id');
            $table->json('legacy_source_payload')->nullable()->after('rental_contract_id');

            $table->foreign('recruitment_contract_id', 'fn_bonds_recruitment_contract_fk')
                ->references('id')->on('contracts')->onDelete('set null');
            $table->foreign('rental_contract_id', 'fn_bonds_rental_contract_fk')
                ->references('id')->on('rental_contracts')->onDelete('set null');
        });

        Schema::create('legacy_finance_bond_source_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bond_id');
            $table->string('source_key', 64)->unique();
            $table->string('source_hash', 64)->index();
            $table->string('source_file');
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->foreign('bond_id', 'legacy_finance_bond_rows_bond_fk')
                ->references('id')->on('fn_bonds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_finance_bond_source_rows');

        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropForeign('fn_bonds_recruitment_contract_fk');
            $table->dropForeign('fn_bonds_rental_contract_fk');
            $table->dropIndex(['legacy_source']);
            $table->dropIndex(['legacy_client_number']);
            $table->dropIndex(['legacy_order_number']);
            $table->dropColumn([
                'legacy_source',
                'legacy_source_file',
                'legacy_source_sheet',
                'legacy_source_row',
                'legacy_sequence',
                'legacy_hijri_date',
                'legacy_total',
                'legacy_currency',
                'legacy_financial_movement',
                'legacy_financial_movement_currency',
                'legacy_client_number',
                'legacy_order_number',
                'recruitment_contract_id',
                'rental_contract_id',
                'legacy_source_payload',
            ]);
        });
    }
};
