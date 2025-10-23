<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'closure_reason')) {
                $table->string('closure_reason')->nullable()->after('resolved_by');
            }

            if (!Schema::hasColumn('complaints', 'closure_attachment')) {
                $table->string('closure_attachment')->nullable()->after('closure_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'closure_reason')) {
                $table->dropColumn('closure_reason');
            }

            if (Schema::hasColumn('complaints', 'closure_attachment')) {
                $table->dropColumn('closure_attachment');
            }
        });
    }
};
