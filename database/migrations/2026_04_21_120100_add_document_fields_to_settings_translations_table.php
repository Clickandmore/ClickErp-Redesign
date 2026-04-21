<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('settings_translations', 'document_company_name')) {
                $table->string('document_company_name')->nullable()->after('website_name');
            }
            if (!Schema::hasColumn('settings_translations', 'document_office_name')) {
                $table->string('document_office_name')->nullable()->after('document_company_name');
            }
            if (!Schema::hasColumn('settings_translations', 'document_footer_note')) {
                $table->text('document_footer_note')->nullable()->after('document_office_name');
            }
            if (!Schema::hasColumn('settings_translations', 'delivery_form_title')) {
                $table->string('delivery_form_title')->nullable()->after('document_footer_note');
            }
            if (!Schema::hasColumn('settings_translations', 'delivery_form_default_text')) {
                $table->text('delivery_form_default_text')->nullable()->after('delivery_form_title');
            }
            if (!Schema::hasColumn('settings_translations', 'shelter_receipt_title')) {
                $table->string('shelter_receipt_title')->nullable()->after('delivery_form_default_text');
            }
            if (!Schema::hasColumn('settings_translations', 'shelter_receipt_subtitle')) {
                $table->string('shelter_receipt_subtitle')->nullable()->after('shelter_receipt_title');
            }
            if (!Schema::hasColumn('settings_translations', 'shelter_receipt_default_text')) {
                $table->text('shelter_receipt_default_text')->nullable()->after('shelter_receipt_subtitle');
            }
            if (!Schema::hasColumn('settings_translations', 'shelter_receipt_default_settlement_text')) {
                $table->text('shelter_receipt_default_settlement_text')->nullable()->after('shelter_receipt_default_text');
            }
            if (!Schema::hasColumn('settings_translations', 'invoice_title')) {
                $table->string('invoice_title')->nullable()->after('shelter_receipt_default_settlement_text');
            }
            if (!Schema::hasColumn('settings_translations', 'invoice_subtitle')) {
                $table->string('invoice_subtitle')->nullable()->after('invoice_title');
            }
            if (!Schema::hasColumn('settings_translations', 'invoice_footer_text')) {
                $table->text('invoice_footer_text')->nullable()->after('invoice_subtitle');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_title')) {
                $table->string('rental_contract_title')->nullable()->after('invoice_footer_text');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_intro_text')) {
                $table->text('rental_contract_intro_text')->nullable()->after('rental_contract_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            $columns = [
                'document_company_name',
                'document_office_name',
                'document_footer_note',
                'delivery_form_title',
                'delivery_form_default_text',
                'shelter_receipt_title',
                'shelter_receipt_subtitle',
                'shelter_receipt_default_text',
                'shelter_receipt_default_settlement_text',
                'invoice_title',
                'invoice_subtitle',
                'invoice_footer_text',
                'rental_contract_title',
                'rental_contract_intro_text',
            ];

            $existingColumns = array_filter($columns, fn ($column) => Schema::hasColumn('settings_translations', $column));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
