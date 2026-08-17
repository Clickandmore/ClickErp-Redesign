<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE recruitment_invoice_credit_notes MODIFY contract_id BIGINT UNSIGNED NULL');

        if (!Schema::hasColumn('recruitment_invoice_credit_notes', 'ingaz_delegation_id')) {
            Schema::table('recruitment_invoice_credit_notes', function (Blueprint $table) {
                $table->foreignId('ingaz_delegation_id')
                    ->nullable()
                    ->after('contract_id')
                    ->constrained('ingaz_delegations')
                    ->restrictOnDelete();
                $table->index(['ingaz_delegation_id', 'issue_date'], 'credit_notes_delegation_issue_date_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruitment_invoice_credit_notes', 'ingaz_delegation_id')) {
            Schema::table('recruitment_invoice_credit_notes', function (Blueprint $table) {
                $table->dropForeign(['ingaz_delegation_id']);
                $table->dropIndex('credit_notes_delegation_issue_date_index');
                $table->dropColumn('ingaz_delegation_id');
            });
        }

        DB::statement('ALTER TABLE recruitment_invoice_credit_notes MODIFY contract_id BIGINT UNSIGNED NOT NULL');
    }
};
