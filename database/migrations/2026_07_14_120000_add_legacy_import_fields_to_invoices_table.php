<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_invoice_number')->nullable()->unique()->after('id');
            $table->string('legacy_invoice_type')->nullable()->after('legacy_invoice_number');
            $table->string('legacy_document_type')->nullable()->after('legacy_invoice_type');
            $table->string('legacy_client_number')->nullable()->index()->after('legacy_document_type');
            $table->string('legacy_order_number')->nullable()->index()->after('legacy_client_number');
            $table->boolean('legacy_zatca_shared')->nullable()->index()->after('send_to_zatca');
            $table->string('legacy_source_sheet')->nullable()->after('legacy_zatca_shared');
            $table->unsignedInteger('legacy_source_row')->nullable()->after('legacy_source_sheet');
            $table->json('legacy_source_payload')->nullable()->after('legacy_source_row');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['legacy_invoice_number']);
            $table->dropIndex(['legacy_client_number']);
            $table->dropIndex(['legacy_order_number']);
            $table->dropIndex(['legacy_zatca_shared']);
            $table->dropColumn([
                'legacy_invoice_number',
                'legacy_invoice_type',
                'legacy_document_type',
                'legacy_client_number',
                'legacy_order_number',
                'legacy_zatca_shared',
                'legacy_source_sheet',
                'legacy_source_row',
                'legacy_source_payload',
            ]);
        });
    }
};
