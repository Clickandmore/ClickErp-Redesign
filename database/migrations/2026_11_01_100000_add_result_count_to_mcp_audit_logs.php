<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mcp_audit_logs') && !Schema::hasColumn('mcp_audit_logs', 'result_count')) {
            Schema::table('mcp_audit_logs', function (Blueprint $table) {
                $table->unsignedInteger('result_count')->nullable()->after('duration_ms');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mcp_audit_logs', 'result_count')) {
            Schema::table('mcp_audit_logs', function (Blueprint $table) {
                $table->dropColumn('result_count');
            });
        }
    }
};
