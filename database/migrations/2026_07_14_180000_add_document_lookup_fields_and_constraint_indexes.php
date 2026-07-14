<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentLookupFieldsAndConstraintIndexes extends Migration
{
    public function up(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->string('legacy_invoice_type', 20)->nullable()->after('legacy_document_number');
            $table->unsignedBigInteger('legacy_invoice_number')->nullable()->after('legacy_invoice_type');
            $table->unsignedBigInteger('legacy_rental_order_number')->nullable()->after('legacy_invoice_number');

            $table->index('code', 'fn_constraints_code_index');
            $table->index('date', 'fn_constraints_date_index');
            $table->index(
                ['approval_status', 'deleted_at', 'code'],
                'fn_constraints_approval_deleted_code_index'
            );
            $table->index(
                ['legacy_invoice_type', 'legacy_invoice_number'],
                'fn_constraints_invoice_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->dropIndex('fn_constraints_code_index');
            $table->dropIndex('fn_constraints_date_index');
            $table->dropIndex('fn_constraints_approval_deleted_code_index');
            $table->dropIndex('fn_constraints_invoice_lookup_index');
            $table->dropColumn([
                'legacy_invoice_type',
                'legacy_invoice_number',
                'legacy_rental_order_number',
            ]);
        });
    }
}
