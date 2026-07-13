<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contracts', 'legacy_order_id')) {
                $table->unsignedBigInteger('legacy_order_id')->nullable()->unique();
            }
            if (!Schema::hasColumn('rental_contracts', 'legacy_created_by')) {
                $table->string('legacy_created_by')->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'legacy_created_at')) {
                $table->dateTime('legacy_created_at')->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'legacy_created_at_hijri')) {
                $table->string('legacy_created_at_hijri', 32)->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'legacy_source')) {
                $table->string('legacy_source')->nullable();
            }
        });

        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'legacy_start_date_hijri')) {
                $table->string('legacy_start_date_hijri', 32)->nullable();
            }
            if (!Schema::hasColumn('rental_contract_infos', 'legacy_end_date_hijri')) {
                $table->string('legacy_end_date_hijri', 32)->nullable();
            }
            if (!Schema::hasColumn('rental_contract_infos', 'legacy_remaining_days')) {
                $table->integer('legacy_remaining_days')->nullable();
            }
            if (!Schema::hasColumn('rental_contract_infos', 'legacy_used_days')) {
                $table->unsignedInteger('legacy_used_days')->nullable();
            }
            if (!Schema::hasColumn('rental_contract_infos', 'legacy_source_payload')) {
                $table->json('legacy_source_payload')->nullable();
            }
        });

        Schema::table('rental_contract_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_invoices', 'legacy_invoice_id')) {
                $table->unsignedBigInteger('legacy_invoice_id')->nullable()->unique();
            }
            if (!Schema::hasColumn('rental_contract_invoices', 'legacy_receipt_code')) {
                $table->unsignedBigInteger('legacy_receipt_code')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_invoices', function (Blueprint $table) {
            foreach (['legacy_invoice_id', 'legacy_receipt_code'] as $column) {
                if (Schema::hasColumn('rental_contract_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('rental_contract_infos', function (Blueprint $table) {
            foreach ([
                'legacy_start_date_hijri',
                'legacy_end_date_hijri',
                'legacy_remaining_days',
                'legacy_used_days',
                'legacy_source_payload',
            ] as $column) {
                if (Schema::hasColumn('rental_contract_infos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('rental_contracts', function (Blueprint $table) {
            foreach ([
                'legacy_order_id',
                'legacy_created_by',
                'legacy_created_at',
                'legacy_created_at_hijri',
                'legacy_source',
            ] as $column) {
                if (Schema::hasColumn('rental_contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
