<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تعليق السير الذاتية لمكتب خارجي: نحفظ وقت التعليق ومن نفذه،
     * مع لقطة بأرقام السير التي كانت ظاهرة وقت التعليق حتى تعود هي فقط عند فك التعليق.
     */
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_offices')) {
            return;
        }

        Schema::table('recruitment_offices', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_offices', 'cvs_suspended_at')) {
                $table->timestamp('cvs_suspended_at')->nullable();
            }

            if (! Schema::hasColumn('recruitment_offices', 'cvs_suspended_by')) {
                $table->unsignedBigInteger('cvs_suspended_by')->nullable();
            }

            if (! Schema::hasColumn('recruitment_offices', 'cvs_suspension_snapshot')) {
                $table->longText('cvs_suspension_snapshot')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recruitment_offices')) {
            return;
        }

        Schema::table('recruitment_offices', function (Blueprint $table) {
            foreach (['cvs_suspended_at', 'cvs_suspended_by', 'cvs_suspension_snapshot'] as $column) {
                if (Schema::hasColumn('recruitment_offices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
