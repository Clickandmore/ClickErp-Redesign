<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_infos', 'escape_report_note')) {
                $table->text('escape_report_note')
                    ->nullable()
                    ->after('outside_shelter_by');
            }

            if (!Schema::hasColumn('contract_infos', 'escape_report_attachment')) {
                $table->json('escape_report_attachment')
                    ->nullable()
                    ->after('escape_report_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('contract_infos', 'escape_report_attachment')) {
                $table->dropColumn('escape_report_attachment');
            }

            if (Schema::hasColumn('contract_infos', 'escape_report_note')) {
                $table->dropColumn('escape_report_note');
            }
        });
    }
};
