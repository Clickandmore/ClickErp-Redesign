<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'general_direct_label')) {
                $table->string('general_direct_label')->nullable()->after('general_description');
            }

            if (!Schema::hasColumn('invoices', 'general_external_label')) {
                $table->string('general_external_label')->nullable()->after('general_direct_label');
            }

            if (!Schema::hasColumn('invoices', 'general_government_label')) {
                $table->string('general_government_label')->nullable()->after('general_external_label');
            }

            if (!Schema::hasColumn('invoices', 'general_ticket_label')) {
                $table->string('general_ticket_label')->nullable()->after('general_government_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach ([
                'general_ticket_label',
                'general_government_label',
                'general_external_label',
                'general_direct_label',
            ] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
