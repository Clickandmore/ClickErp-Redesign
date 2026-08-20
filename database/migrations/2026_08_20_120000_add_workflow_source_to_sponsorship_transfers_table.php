<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_transfers', 'workflow_source')) {
                $table->string('workflow_source', 30)
                    ->default('standard')
                    ->after('external_operation_number')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('sponsorship_transfers', 'workflow_source')) {
                $table->dropColumn('workflow_source');
            }
        });
    }
};
