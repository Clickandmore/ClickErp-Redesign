<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'arabia_client_number')) {
                $table->unsignedBigInteger('arabia_client_number')->nullable()->unique()->after('id');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'arabia_order_number')) {
                $table->unsignedBigInteger('arabia_order_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('contracts', 'arabia_status')) {
                $table->string('arabia_status', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('contracts', 'arabia_supplier_invoice_number')) {
                $table->unsignedBigInteger('arabia_supplier_invoice_number')->nullable()->index()->after('arabia_status');
            }
        });

        Schema::create('arabia_recruitment_import_rows', function (Blueprint $table) {
            $table->id();
            $table->char('source_file_hash', 64);
            $table->string('source_file_name');
            $table->string('sheet_name', 100);
            $table->unsignedInteger('source_row_number');
            $table->unsignedBigInteger('arabia_order_number')->nullable()->index();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->char('row_hash', 64);
            $table->json('payload');
            $table->timestamps();

            $table->unique(
                ['source_file_hash', 'sheet_name', 'source_row_number'],
                'arabia_recruitment_source_row_unique'
            );
            $table->index(['sheet_name', 'row_hash'], 'arabia_recruitment_row_hash_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arabia_recruitment_import_rows');

        Schema::table('contracts', function (Blueprint $table) {
            foreach (['arabia_order_number', 'arabia_status', 'arabia_supplier_invoice_number'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'arabia_client_number')) {
                $table->dropColumn('arabia_client_number');
            }
        });
    }
};
