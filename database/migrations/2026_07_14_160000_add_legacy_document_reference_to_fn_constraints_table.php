<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_constraints')) {
            return;
        }

        Schema::table('fn_constraints', function (Blueprint $table) {
            if (!Schema::hasColumn('fn_constraints', 'legacy_document_type')) {
                $table->string('legacy_document_type', 20)->nullable()->after('code');
            }
            if (!Schema::hasColumn('fn_constraints', 'legacy_document_number')) {
                $table->unsignedBigInteger('legacy_document_number')->nullable()->after('legacy_document_type');
            }
            if (!Schema::hasColumn('fn_constraints', 'legacy_source_file')) {
                $table->string('legacy_source_file')->nullable()->after('legacy_document_number');
            }
            if (!Schema::hasColumn('fn_constraints', 'legacy_source_row')) {
                $table->unsignedInteger('legacy_source_row')->nullable()->after('legacy_source_file');
            }
        });

        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->index(
                ['legacy_document_type', 'legacy_document_number'],
                'fn_constraints_legacy_document_index'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fn_constraints')) {
            return;
        }

        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->dropIndex('fn_constraints_legacy_document_index');
            $table->dropColumn([
                'legacy_document_type',
                'legacy_document_number',
                'legacy_source_file',
                'legacy_source_row',
            ]);
        });
    }
};
