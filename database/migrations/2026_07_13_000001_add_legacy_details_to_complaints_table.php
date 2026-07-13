<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $columns = Schema::getColumnListing('complaints');

            if (!in_array('source_sequence', $columns, true)) {
                $table->string('source_sequence')->nullable()->after('external_ref');
            }
            if (!in_array('complainant_type', $columns, true)) {
                $table->string('complainant_type')->nullable()->after('complainant_phone');
            }
            if (!in_array('against_type', $columns, true)) {
                $table->string('against_type')->nullable()->after('against_name');
            }
            if (!in_array('against_phone', $columns, true)) {
                $table->string('against_phone')->nullable()->after('against_type');
            }
            if (!in_array('complaint_date', $columns, true)) {
                $table->date('complaint_date')->nullable()->after('related_to');
            }
            if (!in_array('source_created_by', $columns, true)) {
                $table->string('source_created_by')->nullable()->after('complaint_date');
            }
            if (!in_array('source_created_at', $columns, true)) {
                $table->dateTime('source_created_at')->nullable()->after('source_created_by');
            }
            if (!in_array('source_status', $columns, true)) {
                $table->string('source_status')->nullable()->after('source_created_at');
            }
            if (!in_array('closed_at', $columns, true)) {
                $table->dateTime('closed_at')->nullable()->after('source_status');
            }
            if (!in_array('closed_by_name', $columns, true)) {
                $table->string('closed_by_name')->nullable()->after('closed_at');
            }
            if (!in_array('import_payload', $columns, true)) {
                $table->json('import_payload')->nullable()->after('closed_by_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $columns = [
                'source_sequence',
                'complainant_type',
                'against_type',
                'against_phone',
                'complaint_date',
                'source_created_by',
                'source_created_at',
                'source_status',
                'closed_at',
                'closed_by_name',
                'import_payload',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('complaints', $column)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
