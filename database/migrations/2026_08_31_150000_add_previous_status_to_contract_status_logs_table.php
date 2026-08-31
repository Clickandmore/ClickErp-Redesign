<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_status_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_status_logs', 'previous_status')) {
                $table->string('previous_status')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                $table->dropColumn('previous_status');
            }
        });
    }
};
